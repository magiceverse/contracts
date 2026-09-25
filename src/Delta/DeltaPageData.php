<?php

namespace Magiceverse\Contracts\Delta;

use Magiceverse\Contracts\Product\ProductData;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * One page of the product delta feed (delta-page v1).
 */
#[MapName(SnakeCaseMapper::class)]
class DeltaPageData extends Data
{
    /**
     * @param  array<string, string>  $schema  contract version per entity, e.g. ['product' => '1.0.0']
     * @param  list<ProductData>  $data  sorted by (updated_at, uid), tombstones inline
     */
    public function __construct(
        public array $schema,
        #[DataCollectionOf(ProductData::class)]
        public array $data,
        public CursorData $cursor,
        public string $generatedAt,
    ) {}
}
