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

    /**
     * Midpoint between two neighbours, or null when they are adjacent and no
     * integer fits between them. A null neighbour means the start or the end.
     */
    public static function between(?int $before, ?int $after): ?int
    {
        if ($after === null) {
            return self::after($before);
        }

        $low = $before ?? 0;
        $middle = intdiv($low + $after, 2);

        return $middle > $low && $middle < $after ? $middle : null;
    }

    /**
     * Where to insert an item at $index among its siblings, which are sorted
     * by position and exclude the item itself. Usually one write; when the
     * neighbours have no gap left, every sibling is renumbered first.
     *
     * @param  array<int, int>  $siblings
     * @return array{position: int, renumbered: list<int>|null}
     */
    public static function insert(array $siblings, int $index): array
    {
        $siblings = array_values($siblings);
        $index = max(0, min($index, count($siblings)));

        $position = self::between($siblings[$index - 1] ?? null, $siblings[$index] ?? null);

        if ($position !== null) {
            return ['position' => $position, 'renumbered' => null];
        }

        // Renumbered to GAP, 2·GAP, …, the siblings around $index are a full
        // GAP apart again, so the middle of that gap is always free.
        return [
            'position' => $index * self::GAP + intdiv(self::GAP, 2),
            'renumbered' => array_map(fn (int $i): int => ($i + 1) * self::GAP, array_keys($siblings)),
        ];
    }
}
