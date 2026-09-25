<?php

namespace Magiceverse\Contracts\Delta;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Where the next delta request continues. `next` is opaque to consumers;
 * it is also set on the last page so a consumer can poll for later changes.
 */
#[MapName(SnakeCaseMapper::class)]
class CursorData extends Data
{
    public function __construct(
        public ?string $next,
        public bool $hasMore,
    ) {}
}
