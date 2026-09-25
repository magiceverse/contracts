<?php

use Magiceverse\Contracts\ContractViolation;
use Magiceverse\Contracts\Schema;

$valid = fn () => array_merge(...array_map(fn ($entity) => array_map(fn ($path) => [$entity, $path], fixtureFiles($entity)), ENTITIES));
$invalid = fn () => array_merge(...array_map(fn ($entity) => array_map(fn ($path) => [$entity, $path], fixtureFiles($entity, true)), ENTITIES));

it('has at least three valid and three invalid fixtures per entity', function (string $entity) {
    expect(count(fixtureFiles($entity)))->toBeGreaterThanOrEqual(3)
        ->and(count(fixtureFiles($entity, true)))->toBeGreaterThanOrEqual(3);
})->with(ENTITIES);

it('accepts the valid fixture', function (string $entity, string $path) {
    Schema::validate($entity, 1, json_decode(file_get_contents($path), flags: JSON_THROW_ON_ERROR));
})->with($valid)->throwsNoExceptions();

it('rejects the invalid fixture at the pointer its reason names', function (string $entity, string $path) {
    $reasonFile = substr($path, 0, -5).'.reason.txt';
    expect($reasonFile)->toBeFile();

    preg_match('/^pointer: (\S+)\nreason: .+\n$/', file_get_contents($reasonFile), $reason);
    expect($reason)->not->toBeEmpty("{$reasonFile} must be 'pointer: <json pointer>' and 'reason: <text>'");

    try {
        Schema::validate($entity, 1, json_decode(file_get_contents($path), flags: JSON_THROW_ON_ERROR));
    } catch (ContractViolation $violation) {
        expect($violation->pointers())->toContain($reason[1]);

        return;
    }

    test()->fail("{$path} validated but should not.");
})->with($invalid);
