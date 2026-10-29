<?php

use App\Models\Board;
use App\Models\BoardList;
use App\Models\Card;
use App\Models\User;
use App\Support\Position;

describe('creating lists', function () {
    it('lets members append a list to the board', function () {
        [$board, , $member] = boardWithAdminAndMember();
        BoardList::factory()->for($board)->create(['position' => 4096]);

        $this->actingAs($member)
            ->post(route('boards.lists.store', $board), ['title' => 'Review'])
            ->assertRedirect();

        $list = $board->lists()->get()->last();
        expect($list?->title)->toBe('Review')
            ->and($list?->position)->toBe(4096 + Position::GAP);
    });

    it('validates the title', function (string $title) {
        [$board, , $member] = boardWithAdminAndMember();

        $this->actingAs($member)
            ->post(route('boards.lists.store', $board), ['title' => $title])
            ->assertSessionHasErrors('title');

        expect($board->lists()->count())->toBe(0);
    })->with([
        'missing' => [''],
        'too long' => [str_repeat('a', 101)],
    ]);

    it('forbids non-members', function () {
        [$board] = boardWithAdminAndMember();

        $this->actingAs(User::factory()->create())
            ->post(route('boards.lists.store', $board), ['title' => 'Review'])
            ->assertForbidden();

        expect($board->lists()->count())->toBe(0);
    });
});

describe('renaming lists', function () {
    it('lets members rename a list', function () {
        [$board, , $member] = boardWithAdminAndMember();
        $list = BoardList::factory()->for($board)->create(['title' => 'Todo']);

        $this->actingAs($member)
            ->patch(route('boards.lists.update', [$board, $list]), ['title' => 'To do'])
            ->assertRedirect();

        expect($list->fresh()?->title)->toBe('To do');
    });

    it('forbids non-members', function () {
        [$board] = boardWithAdminAndMember();
        $list = BoardList::factory()->for($board)->create(['title' => 'Todo']);

        $this->actingAs(User::factory()->create())
            ->patch(route('boards.lists.update', [$board, $list]), ['title' => 'Mine'])
            ->assertForbidden();

        expect($list->fresh()?->title)->toBe('Todo');
    });

    it('does not resolve a list of another board', function () {
        [$board, , $member] = boardWithAdminAndMember();
        $foreignList = BoardList::factory()->for(Board::factory())->create();

        $this->actingAs($member)
            ->patch(route('boards.lists.update', [$board, $foreignList]), ['title' => 'Mine'])
            ->assertNotFound();
    });
});

describe('deleting lists', function () {
    it('deletes the list with its cards', function () {
        [$board, , $member] = boardWithAdminAndMember();
        $list = BoardList::factory()->for($board)->create();
        $card = Card::factory()->for($list, 'boardList')->create();

        $this->actingAs($member)
            ->delete(route('boards.lists.destroy', [$board, $list]))
            ->assertRedirect()
            ->assertInertiaFlash('toast.type', 'success');

        $this->assertModelMissing($list);
        $this->assertModelMissing($card);
    });

    it('forbids non-members', function () {
        [$board] = boardWithAdminAndMember();
        $list = BoardList::factory()->for($board)->create();

        $this->actingAs(User::factory()->create())
            ->delete(route('boards.lists.destroy', [$board, $list]))
            ->assertForbidden();

        $this->assertModelExists($list);
    });

    it('does not resolve a list of another board', function () {
        [$board, , $member] = boardWithAdminAndMember();
        $foreignList = BoardList::factory()->create();

        $this->actingAs($member)
            ->delete(route('boards.lists.destroy', [$board, $foreignList]))
            ->assertNotFound();

        $this->assertModelExists($foreignList);
    });
});
