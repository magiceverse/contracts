<?php

namespace Magiceverse\Contracts;

/**
 * The one place contract versions live. Each constant must equal the
 * `version` keyword inside its schema file; a test holds them together.
 * Within a major only additions are allowed (new optional properties),
 * which bump the minor.
 */
final class Version
{
    public const COMMON = '1.0.0';

    public const TECHNIQUE = '1.0.0';

    public const PRINT_POSITION = '1.0.0';

    public const PRODUCT = '1.0.0';

    public const DELTA_PAGE = '1.0.0';

    public const CLOUDEVENT = '1.0.0';

    /**
     * Keyed by the entity name used in schema paths and $ids.
     */
    public const ALL = [
        'common'         => self::COMMON,
        'technique'      => self::TECHNIQUE,
        'print-position' => self::PRINT_POSITION,
        'product'        => self::PRODUCT,
        'delta-page'     => self::DELTA_PAGE,
        'cloudevent'     => self::CLOUDEVENT,
    ];

    /**
     * Version of an entity's contract, e.g. for the `schema` block of a delta page.
     */
    public static function of(string $entity): string
    {
        return self::ALL[$entity] ?? throw new \InvalidArgumentException("Unknown contract entity [{$entity}].");
    }
}
