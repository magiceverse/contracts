<?php

namespace Magiceverse\Contracts\Technique;

/**
 * What the ERP counts when it prices a technique (Technique v1 `unit`).
 */
enum TechniqueUnit: string
{
    case Color = 'color';
    case Position = 'position';
    case Piece = 'piece';
    case Cm2 = 'cm2';
}
