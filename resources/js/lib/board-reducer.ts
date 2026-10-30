import type { BoardList } from '@/types';

/**
 * Changes to the lists of a board. Indexes are counted without the moved
 * item, exactly like the server (App\Support\Position::insert), so the same
 * values are sent to the move endpoints.
 */
export type BoardAction =
    | { type: 'card/moved'; cardId: number; toListId: number; toIndex: number }
    | { type: 'list/moved'; listId: number; toIndex: number };

/**
 * Pure function: returns new lists and never mutates the given ones. Lists
 * and cards that did not change keep their identity. Array order is the
 * display order; `position` values stay as the server sent them and are
 * refreshed when the board reloads after the request.
 */
export function boardReducer(
    lists: BoardList[],
    action: BoardAction,
): BoardList[] {
    switch (action.type) {
        case 'card/moved':
            return moveCard(
                lists,
                action.cardId,
                action.toListId,
                action.toIndex,
            );
        case 'list/moved':
            return moveList(lists, action.listId, action.toIndex);
    }
}

function moveCard(
    lists: BoardList[],
    cardId: number,
    toListId: number,
    toIndex: number,
): BoardList[] {
    const card = lists
        .flatMap((list) => list.cards)
        .find((candidate) => candidate.id === cardId);

    if (!card || !lists.some((list) => list.id === toListId)) {
        return lists;
    }

    return lists.map((list) => {
        const cards = list.cards.filter((candidate) => candidate.id !== cardId);

        if (list.id === toListId) {
            return { ...list, cards: insertAt(cards, card, toIndex) };
        }

        return cards.length === list.cards.length ? list : { ...list, cards };
    });
}

function moveList(
    lists: BoardList[],
    listId: number,
    toIndex: number,
): BoardList[] {
    const list = lists.find((candidate) => candidate.id === listId);

    if (!list) {
        return lists;
    }

    return insertAt(
        lists.filter((candidate) => candidate.id !== listId),
        list,
        toIndex,
    );
}

/** Inserts at `index`, clamped to the bounds of `items`. */
function insertAt<T>(items: T[], item: T, index: number): T[] {
    const at = Math.max(0, Math.min(index, items.length));

    return [...items.slice(0, at), item, ...items.slice(at)];
}
