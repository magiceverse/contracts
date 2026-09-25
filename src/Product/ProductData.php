<?php

namespace Magiceverse\Contracts\Product;

use Magiceverse\Contracts\Common\OmitsEmptyMaps;
use Magiceverse\Contracts\Common\ProvenanceData;
use Magiceverse\Contracts\PrintPosition\PrintPositionData;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\LaravelData\Optional;

/**
 * Product v1. Every property the schema does not require is Optional, so a
 * tombstone (identity only) and a full product share one class and both
 * come back out of toArray() exactly as they went in.
 */
#[MapName(SnakeCaseMapper::class)]
class ProductData extends Data
{
    use OmitsEmptyMaps;

    protected const MAPS = [
        'values' => [
            'common'                  => 1,
            'locale_specific'         => 2,
            'channel_specific'        => 2,
            'channel_locale_specific' => 3,
        ],
        'provenance' => 1,
    ];

    /**
     * @param  list<string>|Optional  $categories  category codes
     * @param  list<string>|Optional  $channels  channel codes
     * @param  array<string, array<string, mixed>>|Optional  $values  UnoPim values layout (common, locale_specific, ...)
     * @param  array<string, ProvenanceData>|Optional  $provenance  keyed by attribute code
     * @param  list<MediaData>|Optional  $media
     * @param  list<string>|Optional  $variants  variant uids
     * @param  list<string>|Optional  $superAttributes  attribute codes
     * @param  list<PrintPositionData>|Optional  $printPositions
     * @param  list<string>|Optional  $techniques  technique codes
     */
    public function __construct(
        public string $uid,
        public string $sku,
        public ProductType $type,
        public ProductStatus $status,
        public string $updatedAt,
        public string|null|Optional $parentUid,
        public string|null|Optional $family,
        public array|Optional $categories,
        public array|Optional $channels,
        public array|Optional $values,
        #[DataCollectionOf(ProvenanceData::class)]
        public array|Optional $provenance,
        #[DataCollectionOf(MediaData::class)]
        public array|Optional $media,
        public array|Optional $variants,
        public array|Optional $superAttributes,
        #[DataCollectionOf(PrintPositionData::class)]
        public array|Optional $printPositions,
        public array|Optional $techniques,
        public MasterData|null|Optional $master,
        public string|Optional $createdAt,
        public string|null|Optional $deletedAt,
    ) {}

    /**
     * A tombstone tells consumers the product is gone; it has no content.
     */
    public function isTombstone(): bool
    {
        return is_string($this->deletedAt);
    }
}
