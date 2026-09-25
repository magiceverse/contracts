<?php

namespace Magiceverse\Contracts\Product;

use Magiceverse\Contracts\Common\ProvenanceData;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

/**
 * One file of an image, file or gallery attribute (Product v1 `media[]`).
 */
class MediaData extends Data
{
    public function __construct(
        public string $attribute,
        public string $url,
        public string $filename,
        public int $position,
        public string|null|Optional $mime,
        public ProvenanceData|Optional $provenance,
    ) {}
}
