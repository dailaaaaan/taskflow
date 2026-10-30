import { describe, expect, it } from 'vite-plus/test';
import {
    cardDndId,
    cardDropTarget,
    isArrowKey,
    isCardAt,
    keyboardTarget,
    listDndId,
    listDropIndex,
    listOfCard,
} from '@/lib/board-dnd';
import type { BoardList } from '@/types';

function list(id: number, cardIds: number[]): BoardList {
    return {
        id,
        title: `List ${id}`,
        position: id * 1024,
        cards: cardIds.map((cardId) => ({
            id: cardId,
            title: `Card ${cardId}`,
            description: null,
            position: cardId * 1024,
            due_date: null,
            assignee: null,
        })),
    };
}

const board = [list(1, [10, 11, 12]), list(2, [20, 21]), list(3, [])];

describe('dnd ids', () => {
    it('never collide between a card and a list with the same id', () => {
        expect(cardDndId(1)).not.toBe(listDndId(1));
    });
});

describe('listOfCard', () => {
    it('finds the list that holds a card', () => {
        expect(listOfCard(board, 21)?.id).toBe(2);
        expect(listOfCard(board, 999)).toBeUndefined();
    });
});

describe('cardDropTarget', () => {
    it('takes the slot of the card it is dropped on, in the same list', () => {
        expect(
            cardDropTarget(board, 10, { type: 'card', cardId: 12, listId: 1 }),
        ).toEqual({ listId: 1, index: 2 });
    });

    it('takes the slot of the card it is dropped on, in another list', () => {
        expect(
            cardDropTarget(board, 10, { type: 'card', cardId: 21, listId: 2 }),
        ).toEqual({ listId: 2, index: 1 });
    });

    it('goes to the end of a list it is dropped on', () => {
        expect(cardDropTarget(board, 10, { type: 'list', listId: 2 })).toEqual({
            listId: 2,
            index: 2,
        });
        expect(cardDropTarget(board, 10, { type: 'list', listId: 3 })).toEqual({
            listId: 3,
            index: 0,
        });
    });

    it('does not count the card itself at the end of its own list', () => {
        expect(cardDropTarget(board, 10, { type: 'list', listId: 1 })).toEqual({
            listId: 1,
            index: 2,
        });
    });

    it('trusts the current lists over stale drag data', () => {
        // Card 21 was previewed in list 3 while its data still says list 2.
        const previewed = [list(1, [10]), list(2, [20]), list(3, [21])];

        expect(
            cardDropTarget(previewed, 10, {
                type: 'card',
                cardId: 21,
                listId: 2,
            }),
        ).toEqual({ listId: 3, index: 0 });
    });

    it('returns null for an unknown target', () => {
        expect(
            cardDropTarget(board, 10, { type: 'card', cardId: 999, listId: 1 }),
        ).toBeNull();
        expect(
            cardDropTarget(board, 10, { type: 'list', listId: 999 }),
        ).toBeNull();
    });
});

describe('isCardAt', () => {
    it('detects a drop on the current spot', () => {
        expect(isCardAt(board, 11, { listId: 1, index: 1 })).toBe(true);
        expect(isCardAt(board, 11, { listId: 1, index: 0 })).toBe(false);
        expect(isCardAt(board, 11, { listId: 2, index: 1 })).toBe(false);
    });
});

describe('keyboardTarget', () => {
    // Two columns, 200px wide with a 20px gap; cards are inset by 10px.
    const column = (left: number) => ({
        top: 0,
        left,
        right: left + 200,
        bottom: 400,
    });
    const cardAt = (left: number, top: number) => ({
        top,
        left: left + 10,
        right: left + 190,
        bottom: top + 50,
    });
    const targets = [
        { type: 'list' as const, rect: column(0) },
        { type: 'list' as const, rect: column(220) },
        { type: 'list' as const, rect: column(440) },
        { type: 'card' as const, rect: cardAt(0, 100) },
        { type: 'card' as const, rect: cardAt(0, 160) },
        { type: 'card' as const, rect: cardAt(220, 40) },
    ];

    it('moves a card to the nearest list on either side', () => {
        const from = cardAt(220, 40);

        expect(keyboardTarget('card', from, 'ArrowRight', targets)).toEqual(
            column(440),
        );
        expect(keyboardTarget('card', from, 'ArrowLeft', targets)).toEqual(
            column(0),
        );
    });

    it('never targets the list that holds the card', () => {
        expect(
            keyboardTarget('card', cardAt(0, 40), 'ArrowLeft', targets),
        ).toBeNull();
    });

    it('moves a card to the nearest card above or below in its column', () => {
        const from = cardAt(0, 40);

        expect(keyboardTarget('card', from, 'ArrowDown', targets)).toEqual(
            cardAt(0, 100),
        );
        expect(
            keyboardTarget('card', cardAt(0, 160), 'ArrowUp', targets),
        ).toEqual(cardAt(0, 100));
        expect(
            keyboardTarget('card', cardAt(0, 160), 'ArrowDown', targets),
        ).toBeNull();
    });

    it('moves a list sideways only, to the nearest list', () => {
        const from = column(440);

        expect(keyboardTarget('list', from, 'ArrowLeft', targets)).toEqual(
            column(220),
        );
        expect(keyboardTarget('list', from, 'ArrowUp', targets)).toBeNull();
    });

    it('recognises the arrow keys only', () => {
        expect(isArrowKey('ArrowLeft')).toBe(true);
        expect(isArrowKey('Space')).toBe(false);
    });
});

describe('listDropIndex', () => {
    it('finds the index of the list under a list or one of its cards', () => {
        expect(listDropIndex(board, { type: 'list', listId: 3 })).toBe(2);
        expect(
            listDropIndex(board, { type: 'card', cardId: 20, listId: 2 }),
        ).toBe(1);
        expect(listDropIndex(board, { type: 'list', listId: 999 })).toBe(-1);
    });
});
