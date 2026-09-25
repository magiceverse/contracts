<?php

namespace Magiceverse\Contracts\Product;

/**
 * Product v1 `type`.
 */
enum ProductType: string
{
    case Simple = 'simple';
    case Configurable = 'configurable';
}
