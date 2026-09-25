<?php

namespace Magiceverse\Contracts\Technique;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\LaravelData\Optional;

/**
 * Technique v1. Optional properties use Optional rather than a default so
 * that an absent key stays absent in toArray() and a null stays null.
 */
#[MapName(SnakeCaseMapper::class)]
class TechniqueData extends Data
{
    /**
     * @param  array<string, string>  $name  text per locale
     * @param  array<string, string>|Optional  $description  text per locale
     */
    public function __construct(
        public string $code,
        public array $name,
        public int|null|Optional $maxColors,
        public TechniqueUnit|Optional $unit,
        public int|null|Optional $leadDays,
        public array|Optional $description,
    ) {}
}
