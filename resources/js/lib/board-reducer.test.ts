import { describe, expect, it } from 'vite-plus/test';
import { boardReducer } from '@/lib/board-reducer';
import type { BoardList, Card } from '@/types';

function card(id: number): Card {
    return {
        id,
        title: `Card ${id}`,
        description: null,
        position: id * 1024,
        due_date: null,
        assignee: null,
    };
}

function list(id: number, cardIds: number[]): BoardList {
    return {
        id,
        title: `List ${id}`,
        position: id * 1024,
        cards: cardIds.map(card),
    };
}

/** Card ids per list, in display order: `{ 1: [10, 11], 2: [20] }`. */
function layout(lists: BoardList[]): Record<number, number[]> {
    return Object.fromEntries(
        lists.map((item) => [item.id, item.cards.map((c) => c.id)]),
    );
}

describe('card/moved', () => {
    const board = [list(1, [10, 11, 12]), list(2, [20, 21]), list(3, [])];

    it('moves a card down within its list', () => {
        // Index 2 among the other two cards (11, 12): after 12.
        const result = boardReducer(board, {
            type: 'card/moved',
            cardId: 10,
            toListId: 1,
            toIndex: 2,
        });

        expect(layout(result)[1]).toEqual([11, 12, 10]);
    });

    it('moves a card up within its list', () => {
        const result = boardReducer(board, {
            type: 'card/moved',
            cardId: 12,
            toListId: 1,
            toIndex: 0,
        });

        expect(layout(result)[1]).toEqual([12, 10, 11]);
    });

    it('moves a card to a position of another list', () => {
        const result = boardReducer(board, {
            type: 'card/moved',
            cardId: 11,
            toListId: 2,
            toIndex: 1,
        });

        expect(layout(result)).toEqual({ 1: [10, 12], 2: [20, 11, 21], 3: [] });
    });

    it('moves a card into an empty list', () => {
        const result = boardReducer(board, {
            type: 'card/moved',
            cardId: 20,
            toListId: 3,
            toIndex: 0,
        });

        expect(layout(result)).toEqual({ 1: [10, 11, 12], 2: [21], 3: [20] });
    });

    it('clamps an index outside the destination list', () => {
        const atEnd = boardReducer(board, {
            type: 'card/moved',
            cardId: 10,
            toListId: 2,
            toIndex: 99,
        });
        const atStart = boardReducer(board, {
            type: 'card/moved',
            cardId: 10,
            toListId: 2,
            toIndex: -5,
        });

        expect(layout(atEnd)[2]).toEqual([20, 21, 10]);
        expect(layout(atStart)[2]).toEqual([10, 20, 21]);
    });

    it('keeps the lists that did not change', () => {
        const result = boardReducer(board, {
            type: 'card/moved',
            cardId: 10,
            toListId: 1,
            toIndex: 1,
        });

        expect(result[1]).toBe(board[1]);
        expect(result[2]).toBe(board[2]);
        expect(result[0]).not.toBe(board[0]);
    });

    it('returns the same lists for an unknown card or list', () => {
        expect(
            boardReducer(board, {
                type: 'card/moved',
                cardId: 999,
                toListId: 1,
                toIndex: 0,
            }),
        ).toBe(board);
        expect(
            boardReducer(board, {
                type: 'card/moved',
                cardId: 10,
                toListId: 999,
                toIndex: 0,
            }),
        ).toBe(board);
    });

    it('never mutates its input', () => {
        const snapshot = structuredClone(board);

        boardReducer(board, {
            type: 'card/moved',
            cardId: 11,
            toListId: 3,
            toIndex: 0,
        });

        expect(board).toEqual(snapshot);
    });
});

describe('list/moved', () => {
    const board = [list(1, [10]), list(2, []), list(3, [30])];

    it('moves a list to another position of the board', () => {
        const result = boardReducer(board, {
            type: 'list/moved',
            listId: 3,
            toIndex: 0,
        });

        expect(result.map((item) => item.id)).toEqual([3, 1, 2]);
        expect(result[0]).toBe(board[2]);
    });

    it('moves a list to the end of the board', () => {
        const result = boardReducer(board, {
            type: 'list/moved',
            listId: 1,
            toIndex: 2,
        });

        expect(result.map((item) => item.id)).toEqual([2, 3, 1]);
    });

    it('returns the same lists for an unknown list', () => {
        expect(
            boardReducer(board, {
                type: 'list/moved',
                listId: 999,
                toIndex: 0,
            }),
        ).toBe(board);
    });

    it('never mutates its input', () => {
        const snapshot = structuredClone(board);

        boardReducer(board, { type: 'list/moved', listId: 2, toIndex: 0 });

        expect(board).toEqual(snapshot);
    });
});
