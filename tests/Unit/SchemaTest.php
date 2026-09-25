<?php

use Magiceverse\Contracts\ContractViolation;
use Magiceverse\Contracts\Schema;
use Magiceverse\Contracts\Version;
use Opis\JsonSchema\Uri;
use Opis\JsonSchema\Validator;

it('ships one schema per versioned entity', function () {
    expect(array_keys(Schema::all()))->toBe([
        'https://contracts.magiceverse.dev/cloudevent/v1',
        'https://contracts.magiceverse.dev/common/v1',
        'https://contracts.magiceverse.dev/delta-page/v1',
        'https://contracts.magiceverse.dev/print-position/v1',
        'https://contracts.magiceverse.dev/product/v1',
        'https://contracts.magiceverse.dev/technique/v1',
    ]);
});

it('declares the $id that matches its file location', function (string $id, string $path) {
    $schema = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

    expect($schema['$id'])->toBe($id)
        ->and($schema['$schema'])->toBe('https://json-schema.org/draft/2020-12/schema');
})->with(fn () => array_map(fn ($id, $path) => [$id, $path], array_keys(Schema::all()), Schema::all()));

it('keeps Version in step with the version keyword of each schema', function (string $entity, string $version) {
    $schema = json_decode(file_get_contents(Schema::path($entity, 1)), true, flags: JSON_THROW_ON_ERROR);

    expect($schema['version'])->toBe($version)
        ->and(Version::of($entity))->toBe($version);
})->with(fn () => array_map(fn ($entity, $version) => [$entity, $version], array_keys(Version::ALL), Version::ALL));

it('has a Version entry for every shipped schema', function () {
    $entities = array_unique(array_map(fn ($path) => basename(dirname($path)), Schema::all()));
    sort($entities);

    $versioned = array_keys(Version::ALL);
    sort($versioned);

    expect($versioned)->toBe(array_values($entities));
});

it('resolves every $id from the local files without a network', function (string $id) {
    $schema = Schema::validator()->loader()->loadSchemaById(Uri::parse($id, true));

    expect($schema)->not->toBeNull();
})->with(fn () => array_keys(Schema::all()));

it('cannot resolve our $ids without the local registration', function () {
    // Proves the ids are served from disk by Schema::validator(), not
    // fetched: a stock validator has no way to find them.
    $stock = new Validator;

    expect($stock->loader()->loadSchemaById(Uri::parse(Schema::id('product', 1), true)))->toBeNull();
});

it('only references schemas it ships', function (string $path) {
    $refs = [];
    $walk = function ($node) use (&$walk, &$refs) {
        if (! is_array($node)) {
            return;
        }
        foreach ($node as $key => $value) {
            if ($key === '$ref' && is_string($value) && str_starts_with($value, 'http')) {
                $refs[] = explode('#', $value)[0];
            }
            $walk($value);
        }
    };
    $walk(json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR));

    expect(array_diff(array_unique($refs), array_keys(Schema::all())))->toBe([]);
})->with(fn () => array_map(fn ($path) => [$path], Schema::all()));

it('forbids unknown properties on every object', function (string $path) {
    $missing = [];
    $walk = function ($node, string $pointer) use (&$walk, &$missing) {
        if (! is_array($node)) {
            return;
        }
        // Closed objects list their properties; maps constrain their values
        // through additionalProperties with a schema instead of false. An
        // `if` only tests a condition and a `then` refines an object that is
        // already closed where it is declared.
        if (isset($node['properties']) && ($node['additionalProperties'] ?? null) !== false
            && ! str_contains($pointer, '/if') && ! str_contains($pointer, '/then')) {
            $missing[] = $pointer ?: '/';
        }
        foreach ($node as $key => $value) {
            $walk($value, "{$pointer}/{$key}");
        }
    };
    $walk(json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR), '');

    expect($missing)->toBe([]);
})->with(fn () => array_map(fn ($path) => [$path], Schema::all()));

it('points at the local schema file', function () {
    expect(Schema::path('product', 1))->toBe(dirname(__DIR__, 2).'/schemas/product/v1.json');
});

it('refuses a schema it does not ship', function () {
    Schema::path('product', 2);
})->throws(InvalidArgumentException::class, 'No schema for [product] v2.');

it('refuses an unknown entity version', function () {
    Version::of('order');
})->throws(InvalidArgumentException::class, 'Unknown contract entity [order].');

it('accepts both decoded objects and associative arrays', function () {
    $json = file_get_contents(dirname(__DIR__, 2).'/fixtures/technique/v1/screen-print.json');

    Schema::validate('technique', 1, json_decode($json));
    Schema::validate('technique', 1, json_decode($json, true));
})->throwsNoExceptions();

it('reports every error with its JSON pointer in one exception', function () {
    try {
        Schema::validate('technique', 1, ['code' => 'Bad Code', 'name' => ['nl_NL' => 'X'], 'unit' => 'meter', 'lead_days' => -1]);
    } catch (ContractViolation $violation) {
        expect($violation)->toBeInstanceOf(InvalidArgumentException::class)
            ->and($violation->schemaId)->toBe('https://contracts.magiceverse.dev/technique/v1')
            ->and($violation->pointers())->toEqualCanonicalizing(['/code', '/unit', '/lead_days'])
            ->and($violation->getMessage())->toContain('/code: ', '/unit: ', '/lead_days: ');

        return;
    }

    test()->fail('Expected a ContractViolation.');
});

it('reports an unknown property next to a bad value without echoing the declared ones', function () {
    try {
        Schema::validate('technique', 1, ['code' => 'Bad Code', 'name' => ['nl_NL' => 'X'], 'price' => 1]);
    } catch (ContractViolation $violation) {
        expect($violation->errors)->toBe([
            '/code' => ['The string should match pattern: ^[a-z0-9_]+$'],
            '/'     => ['Additional object properties are not allowed: price'],
        ]);

        return;
    }

    test()->fail('Expected a ContractViolation.');
});
