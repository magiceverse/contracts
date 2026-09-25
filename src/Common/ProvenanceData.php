<?php

namespace Magiceverse\Contracts\Common;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\LaravelData\Optional;

/**
 * common v1 `provenance`. Timestamps stay RFC 3339 strings so a value
 * survives a round trip byte for byte.
 */
#[MapName(SnakeCaseMapper::class)]
class ProvenanceData extends Data
{
    public function __construct(
        public Origin $origin,
        public string|Optional $originAt,
        public string|Optional $originBy,
        public bool|Optional $locked,
    ) {}
}
