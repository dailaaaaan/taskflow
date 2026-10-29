<?php

namespace App\Enums;

/**
 * Accent colour of a board. Persisted as its string value in boards.color;
 * the frontend maps each value to a fixed Tailwind class.
 */
enum BoardColor: string
{
    case Indigo = 'indigo';
    case Emerald = 'emerald';
    case Amber = 'amber';
    case Rose = 'rose';
    case Sky = 'sky';
    case Violet = 'violet';
}
