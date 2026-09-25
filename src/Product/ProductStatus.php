<?php

namespace Magiceverse\Contracts\Product;

/**
 * Product v1 `status`.
 */
enum ProductStatus: string
{
    case Enabled = 'enabled';
    case Disabled = 'disabled';
}
