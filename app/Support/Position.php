<?php

namespace App\Support;

/**
 * Sparse integer positions for lists and cards (docs/adr/0003-integer-gap-positions.md).
 * Items are spaced GAP apart, so an item can later be moved between two
 * neighbours by writing a single row.
 */
final class Position
{
    public const int GAP = 1024;

    /**
     * Position for an item appended after $last, or the first position when
     * the collection is empty.
     */
    public static function after(?int $last): int
    {
        return ($last ?? 0) + self::GAP;
    }
}
