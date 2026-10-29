<?php

use App\Support\Position;

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
