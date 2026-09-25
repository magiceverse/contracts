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
