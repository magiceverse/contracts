<?php

namespace Magiceverse\Contracts\Common;

/**
 * Who produced a value (common v1 `provenance.origin`).
 */
enum Origin: string
{
    case Supplier = 'supplier';
    case Ai = 'ai';
    case Human = 'human';
}
