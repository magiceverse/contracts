<?php

namespace Magiceverse\Contracts\PrintPosition;

use Magiceverse\Contracts\Common\ProvenanceData;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\LaravelData\Optional;

/**
 * PrintPosition v1. An absent shape means rectangle (the schema default);
 * it is not filled in here so the round trip keeps the sender's payload.
 */
#[MapName(SnakeCaseMapper::class)]
class PrintPositionData extends Data
{
    /**
     * @param  array<string, string>  $name  text per locale
     * @param  list<string>|Optional  $techniques  technique codes
     */
    public function __construct(
        public string $code,
        public array $name,
        public int|float|null|Optional $maxWidthMm,
        public int|float|null|Optional $maxHeightMm,
        public Shape|Optional $shape,
        public array|Optional $techniques,
        public string|null|Optional $imageUrl,
        public string|null|Optional $modelArea,
        public ProvenanceData|Optional $provenance,
    ) {}
}
