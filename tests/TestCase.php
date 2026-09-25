<?php

namespace Magiceverse\Contracts\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\LaravelData\LaravelDataServiceProvider;

/**
 * laravel-data resolves its pipeline from the container, so the DTO tests
 * need a booted application; the schema tests do not.
 */
abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [LaravelDataServiceProvider::class];
    }
}
