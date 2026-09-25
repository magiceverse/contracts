<?php

use Magiceverse\Contracts\Tests\TestCase;

uses(TestCase::class)->in('Feature');

/**
 * Fixture files for an entity: the valid ones, or the ones under invalid/.
 *
 * @return array<string, string> dataset label => absolute path
 */
function fixtureFiles(string $entity, bool $invalid = false): array
{
    $dir = dirname(__DIR__)."/fixtures/{$entity}/v1".($invalid ? '/invalid' : '');
    $files = [];

    foreach (glob("{$dir}/*.json") ?: [] as $path) {
        $files["{$entity}/".basename($path, '.json')] = $path;
    }

    return $files;
}

/**
 * @return array<string, mixed>
 */
function readFixture(string $path): array
{
    return json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
}

const ENTITIES = ['technique', 'print-position', 'product', 'delta-page', 'cloudevent'];
