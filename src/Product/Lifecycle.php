<?php

namespace Magiceverse\Contracts\Product;

/**
 * Lifecycle of the master product (Product v1 `master.lifecycle`).
 */
enum Lifecycle: string
{
    case New = 'new';
    case Active = 'active';
    case Discontinued = 'discontinued';
    case Withdrawn = 'withdrawn';
}
