<?php

use App\Models\BoardList;
use App\Models\Card;
use App\Models\User;
use App\Support\Position;

describe('creating cards', function () {
    it('lets members append a card to a list', function () {
        [$board, , $member] = boardWithAdminAndMember();
        $list = BoardList::factory()->for($board)->create();
        Card::factory()->for($list, 'boardList')->create(['position' => 1024]);

        $this->actingAs($member)
            ->post(route('boards.lists.cards.store', [$board, $list]), ['title' => 'Write tests'])
            ->assertRedirect();

        $card = $list->cards()->get()->last();
        expect($card?->title)->toBe('Write tests')
            ->and($card?->position)->toBe(1024 + Position::GAP)
            ->and($card?->created_by)->toBe($member->id);
    });

    it('validates the title', function (string $title) {
        [$board, , $member] = boardWithAdminAndMember();
        $list = BoardList::factory()->for($board)->create();

        $this->actingAs($member)
            ->post(route('boards.lists.cards.store', [$board, $list]), ['title' => $title])
            ->assertSessionHasErrors('title');

        expect($list->cards()->count())->toBe(0);
    })->with([
        'missing' => [''],
        'too long' => [str_repeat('a', 201)],
    ]);

    it('forbids non-members', function () {
        [$board] = boardWithAdminAndMember();
        $list = BoardList::factory()->for($board)->create();

        $this->actingAs(User::factory()->create())
            ->post(route('boards.lists.cards.store', [$board, $list]), ['title' => 'Mine'])
            ->assertForbidden();

        expect($list->cards()->count())->toBe(0);
    });

    it('does not resolve a list of another board', function () {
        [$board, , $member] = boardWithAdminAndMember();
        $foreignList = BoardList::factory()->create();

        $this->actingAs($member)
            ->post(route('boards.lists.cards.store', [$board, $foreignList]), ['title' => 'Mine'])
            ->assertNotFound();

        expect($foreignList->cards()->count())->toBe(0);
    });
});

describe('editing cards', function () {
    it('updates the title, description, assignee and due date', function () {
        [$board, $admin, $member] = boardWithAdminAndMember();
        $card = Card::factory()->for(BoardList::factory()->for($board), 'boardList')->create();

        $this->actingAs($member)
            ->patch(route('boards.cards.update', [$board, $card]), [
                'title' => 'Ship v0.5.0',
                'description' => 'Boards, lists and cards.',
                'assignee_id' => $admin->id,
                'due_date' => '2026-11-15',
            ])
            ->assertRedirect();

        expect($card->fresh())
            ->title->toBe('Ship v0.5.0')
            ->description->toBe('Boards, lists and cards.')
            ->assignee_id->toBe($admin->id)
            ->due_date->toDateString()->toBe('2026-11-15');
    });

    it('clears the optional details when they are left empty', function () {
        [$board, $admin, $member] = boardWithAdminAndMember();
        $card = Card::factory()->for(BoardList::factory()->for($board), 'boardList')->create([
            'description' => 'Old',
            'assignee_id' => $admin->id,
            'due_date' => '2026-11-15',
        ]);

        $this->actingAs($member)
            ->patch(route('boards.cards.update', [$board, $card]), [
                'title' => $card->title,
                'description' => '',
                'assignee_id' => '',
                'due_date' => '',
            ])
            ->assertRedirect();

        expect($card->fresh())
            ->description->toBeNull()
            ->assignee_id->toBeNull()
            ->due_date->toBeNull();
    });

    it('only accepts members of the team as assignees', function () {
        [$board, , $member] = boardWithAdminAndMember();
        $card = Card::factory()->for(BoardList::factory()->for($board), 'boardList')->create();

        $this->actingAs($member)
            ->patch(route('boards.cards.update', [$board, $card]), [
                'title' => $card->title,
                'assignee_id' => User::factory()->create()->id,
            ])
            ->assertSessionHasErrors('assignee_id');

        expect($card->fresh()?->assignee_id)->toBeNull();
    });

    it('validates the details', function (array $payload, string $field) {
        [$board, , $member] = boardWithAdminAndMember();
        $card = Card::factory()->for(BoardList::factory()->for($board), 'boardList')->create();

        $this->actingAs($member)
            ->patch(route('boards.cards.update', [$board, $card]), [...['title' => 'Valid'], ...$payload])
            ->assertSessionHasErrors($field);
    })->with([
        'missing title' => [['title' => ''], 'title'],
        'title too long' => [['title' => str_repeat('a', 201)], 'title'],
        'description too long' => [['description' => str_repeat('a', 10001)], 'description'],
        'due date in another format' => [['due_date' => '15/11/2026'], 'due_date'],
    ]);

    it('forbids non-members before validating their input', function () {
        [$board] = boardWithAdminAndMember();
        $card = Card::factory()->for(BoardList::factory()->for($board), 'boardList')->create();

        $this->actingAs(User::factory()->create())
            ->patch(route('boards.cards.update', [$board, $card]), ['title' => ''])
            ->assertForbidden();
    });

    it('does not resolve a card of another board', function () {
        [$board, , $member] = boardWithAdminAndMember();
        $foreignCard = Card::factory()->create(['title' => 'Theirs']);

        $this->actingAs($member)
            ->patch(route('boards.cards.update', [$board, $foreignCard]), ['title' => 'Mine'])
            ->assertNotFound();

        expect($foreignCard->fresh()?->title)->toBe('Theirs');
    });
});

describe('deleting cards', function () {
    it('lets members delete a card', function () {
        [$board, , $member] = boardWithAdminAndMember();
        $card = Card::factory()->for(BoardList::factory()->for($board), 'boardList')->create();

        $this->actingAs($member)
            ->delete(route('boards.cards.destroy', [$board, $card]))
            ->assertRedirect()
            ->assertInertiaFlash('toast.type', 'success');

        $this->assertModelMissing($card);
    });

    it('forbids non-members', function () {
        [$board] = boardWithAdminAndMember();
        $card = Card::factory()->for(BoardList::factory()->for($board), 'boardList')->create();

        $this->actingAs(User::factory()->create())
            ->delete(route('boards.cards.destroy', [$board, $card]))
            ->assertForbidden();

        $this->assertModelExists($card);
    });

    it('does not resolve a card of another board', function () {
        [$board, , $member] = boardWithAdminAndMember();
        $foreignCard = Card::factory()->create();

        $this->actingAs($member)
            ->delete(route('boards.cards.destroy', [$board, $foreignCard]))
            ->assertNotFound();

        $this->assertModelExists($foreignCard);
    });
});
