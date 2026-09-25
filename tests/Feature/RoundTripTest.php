<?php

use Magiceverse\Contracts\Delta\DeltaPageData;
use Magiceverse\Contracts\Envelope\CloudEventData;
use Magiceverse\Contracts\PrintPosition\PrintPositionData;
use Magiceverse\Contracts\Product\ProductData;
use Magiceverse\Contracts\Product\ProductType;
use Magiceverse\Contracts\Schema;
use Magiceverse\Contracts\Technique\TechniqueData;

const DATA_CLASSES = [
    'technique'      => TechniqueData::class,
    'print-position' => PrintPositionData::class,
    'product'        => ProductData::class,
    'delta-page'     => DeltaPageData::class,
    'cloudevent'     => CloudEventData::class,
];

it('round-trips the fixture through its Data class unchanged', function (string $entity, string $path) {
    $input = readFixture($path);

    $output = DATA_CLASSES[$entity]::from($input)->toArray();

    Schema::validate($entity, 1, $output);
    expect($output)->toEqual($input);
})->with(fn () => array_merge(...array_map(
    fn ($entity) => array_map(fn ($path) => [$entity, $path], fixtureFiles($entity)),
    ENTITIES,
)));

it('leaves empty maps out instead of emitting []', function () {
    $input = readFixture(dirname(__DIR__, 2).'/fixtures/product/v1/variant-of-configurable.json');
    $input['values'] = [
        'common'          => [],
        'locale_specific' => ['nl_NL' => [], 'en_US' => ['name' => 'T-shirt', 'materials' => []]],
    ];
    $input['provenance'] = [];

    $output = ProductData::from($input)->toArray();

    Schema::validate('product', 1, $output);
    expect($output['values'])->toBe(['locale_specific' => ['en_US' => ['name' => 'T-shirt', 'materials' => []]]])
        ->and($output)->not->toHaveKey('provenance');
});

it('drops values altogether when every bucket is empty', function () {
    $input = readFixture(dirname(__DIR__, 2).'/fixtures/product/v1/variant-of-configurable.json');
    $input['values'] = ['common' => [], 'channel_locale_specific' => ['webshop' => ['nl_NL' => []]]];

    $output = ProductData::from($input)->toArray();

    Schema::validate('product', 1, $output);
    expect($output)->not->toHaveKey('values')
        ->and($output['categories'])->toBe(['t_shirts']);
});

it('leaves an empty technique description out', function () {
    $output = TechniqueData::from(['code' => 'pad_print', 'name' => ['nl_NL' => 'Tampondruk'], 'description' => []])->toArray();

    Schema::validate('technique', 1, $output);
    expect($output)->toBe(['code' => 'pad_print', 'name' => ['nl_NL' => 'Tampondruk']]);
});

it('keeps empty lists, which carry meaning', function () {
    $output = ProductData::from(readFixture(dirname(__DIR__, 2).'/fixtures/product/v1/minimal-unpublished.json'))->toArray();

    expect($output['channels'])->toBe([]);
});

it('prunes empty maps in nested products too', function () {
    $page = readFixture(dirname(__DIR__, 2).'/fixtures/delta-page/v1/last-page.json');
    $page['data'][1]['values'] = ['common' => []];

    $output = DeltaPageData::from($page)->toArray();

    Schema::validate('delta-page', 1, $output);
    expect($output['data'][1])->not->toHaveKey('values');
});

it('types enums and nested data', function () {
    $product = ProductData::from(readFixture(dirname(__DIR__, 2).'/fixtures/product/v1/simple-with-media-and-positions.json'));

    expect($product->type)->toBe(ProductType::Simple)
        ->and($product->printPositions[0])->toBeInstanceOf(PrintPositionData::class)
        ->and($product->master->supplierSku)->toBe('56-0602045')
        ->and($product->provenance['ean']->locked)->toBeTrue()
        ->and($product->isTombstone())->toBeFalse();
});

it('recognises a tombstone', function () {
    $product = ProductData::from(readFixture(dirname(__DIR__, 2).'/fixtures/product/v1/tombstone.json'));

    expect($product->isTombstone())->toBeTrue();
});
