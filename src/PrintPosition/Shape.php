<?php

namespace Magiceverse\Contracts\PrintPosition;

/**
 * PrintPosition v1 `shape`.
 */
enum Shape: string
{
    case Rectangle = 'rectangle';
    case Circle = 'circle';
    case Free = 'free';
}
