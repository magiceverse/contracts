<?php

namespace Magiceverse\Contracts\Common;

use Spatie\LaravelData\Support\Transformation\TransformationContext;
use Spatie\LaravelData\Support\Transformation\TransformationContextFactory;

/**
 * JSON objects decode to PHP arrays, and an empty PHP array encodes back as
 * [] rather than {}, which the schemas reject. Empty maps carry no
 * information, so they are left out of the output altogether.
 *
 * The using class declares MAPS: output key => how many map levels deep the
 * value goes (1 = {key: value}), or an array of the same for nested keys.
 * Lists are never touched, since an empty list does carry meaning.
 */
trait OmitsEmptyMaps
{
    /** @return array<string, mixed> */
    public function transform(
        null|TransformationContextFactory|TransformationContext $transformationContext = null,
    ): array {
        return self::pruneMaps(parent::transform($transformationContext), static::MAPS);
    }

    /**
     * @param  array<array-key, mixed>  $map
     * @param  int|array<string, int|array<string, mixed>>  $shape
     * @return array<array-key, mixed>
     */
    private static function pruneMaps(array $map, int|array $shape): array
    {
        $children = is_int($shape)
            ? ($shape > 1 ? array_fill_keys(array_keys($map), $shape - 1) : [])
            : $shape;

        foreach ($children as $key => $childShape) {
            if (! is_array($map[$key] ?? null)) {
                continue;
            }

            $map[$key] = self::pruneMaps($map[$key], $childShape);

            if ($map[$key] === []) {
                unset($map[$key]);
            }
        }

        return $map;
    }
}
