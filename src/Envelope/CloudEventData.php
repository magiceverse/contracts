<?php

namespace Magiceverse\Contracts\Envelope;

use Magiceverse\Contracts\Product\ProductData;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

/**
 * Webhook envelope (cloudevent v1): CloudEvents 1.0 structured mode with a
 * Product as data. Property names are CloudEvents attribute names, which
 * are lowercase without separators by definition.
 */
class CloudEventData extends Data
{
    public function __construct(
        public string $specversion,
        public EventType $type,
        public string $source,
        public string $id,
        public string $time,
        public string $subject,
        public string $datacontenttype,
        public ProductData $data,
        public string $tenant,
        public string|Optional $correlationid,
    ) {}
}
