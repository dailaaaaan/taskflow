<?php

use App\Support\Position;

describe('after', function () {
    it('starts an empty collection one gap from zero', function () {
        expect(Position::after(null))->toBe(Position::GAP);
    });

    it('appends one gap after the last position', function (int $last, int $expected) {
        expect(Position::after($last))->toBe($expected);
    })->with([
        'after the first item' => [1024, 2048],
        'after an item placed between two others' => [1536, 2560],
        'after a position of zero' => [0, 1024],
    ]);
});

describe('between', function () {
    it('returns the midpoint of two neighbours', function (?int $before, ?int $after, int $expected) {
        expect(Position::between($before, $after))->toBe($expected);
    })->with([
        'between two items' => [1024, 2048, 1536],
        'before the first item' => [null, 1024, 512],
        'after the last item' => [2048, null, 3072],
        'in an empty collection' => [null, null, 1024],
        'with an odd gap' => [1024, 1027, 1025],
    ]);

    it('returns null when no integer fits between the neighbours', function (?int $before, int $after) {
        expect(Position::between($before, $after))->toBeNull();
    })->with([
        'adjacent items' => [1024, 1025],
        'equal positions' => [1024, 1024],
        'before an item at position 1' => [null, 1],
    ]);
});

describe('insert', function () {
    it('takes the midpoint of the neighbours at the index without renumbering', function (int $index, int $expected) {
        expect(Position::insert([1024, 2048, 3072], $index))
            ->toBe(['position' => $expected, 'renumbered' => null]);
    })->with([
        'at the start' => [0, 512],
        'in the middle' => [1, 1536],
        'at the end' => [3, 4096],
    ]);

    it('starts an empty collection one gap from zero', function () {
        expect(Position::insert([], 0))->toBe(['position' => Position::GAP, 'renumbered' => null]);
    });

    it('clamps an index outside the collection', function (int $index, int $expected) {
        expect(Position::insert([1024, 2048], $index)['position'])->toBe($expected);
    })->with([
        'negative' => [-3, 512],
        'past the end' => [99, 3072],
    ]);

    it('renumbers the siblings when the neighbours have no gap left', function () {
        expect(Position::insert([1024, 1025, 3000], 1))->toBe([
            'position' => 1536,
            'renumbered' => [1024, 2048, 3072],
        ]);
    });

    it('renumbers the siblings when there is no room before the first one', function () {
        expect(Position::insert([1, 5], 0))->toBe([
            'position' => 512,
            'renumbered' => [1024, 2048],
        ]);
    });

    it('keeps the order when the result is applied', function (array $siblings, int $index) {
        $placement = Position::insert($siblings, $index);
        $positions = $placement['renumbered'] ?? $siblings;
        array_splice($positions, $index, 0, [$placement['position']]);

        $sorted = $positions;
        sort($sorted);

        expect($positions)->toBe($sorted)
            ->and(array_unique($positions))->toHaveCount(count($positions));
    })->with([
        'crowded middle' => [[10, 11, 12, 13], 2],
        'crowded start' => [[1, 2, 3], 0],
        'spacious' => [[1024, 5000, 9000], 2],
        'append' => [[1, 2], 2],
    ]);
});
