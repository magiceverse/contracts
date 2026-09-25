<?php

namespace Magiceverse\Contracts\Product;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\LaravelData\Optional;

/**
 * Link from a tenant product to the master product it was derived from
 * (Product v1 `master`).
 */
#[MapName(SnakeCaseMapper::class)]
class MasterData extends Data
{
    public function __construct(
        public string $uid,
        public int $version,
        public string $publishedAt,
        public Lifecycle $lifecycle,
        public string|null|Optional $supplier,
        public string|null|Optional $supplierSku,
    ) {}
}
