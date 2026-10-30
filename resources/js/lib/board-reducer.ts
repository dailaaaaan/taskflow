import type { BoardList, Card } from '@/types';

/**
 * Changes to the lists of a board: the user's own moves and the ones
 * broadcast by App\Events. Indexes are counted without the moved item,
 * exactly like the server (App\Support\Position::insert).
 */
export type BoardAction =
    | { type: 'card/created'; listId: number; card: Card }
    | { type: 'card/updated'; card: Card }
    | { type: 'card/moved'; cardId: number; toListId: number; toIndex: number }
    | { type: 'card/deleted'; cardId: number }
    | { type: 'list/created'; list: Omit<BoardList, 'cards'> }
    | { type: 'list/renamed'; listId: number; title: string }
    | { type: 'list/moved'; listId: number; toIndex: number }
    | { type: 'list/deleted'; listId: number };

/**
 * What a client receives on the board channel: an action, or `board/stale`
 * when the action was too large for an event and the lists must be reloaded.
 */
export type BoardEvent = BoardAction | { type: 'board/stale' };

/**
 * Pure function: returns new lists and never mutates the given ones. Lists
 * and cards that did not change keep their identity. Array order is the
 * display order; `position` values stay as the server sent them and are
 * refreshed when the board reloads after the request.
 *
 * Remote actions can arrive after a reload that already includes them, so
 * every action tolerates items that already exist or no longer do.
 */
export function boardReducer(
    lists: BoardList[],
    action: BoardAction,
): BoardList[] {
    switch (action.type) {
        case 'card/created':
            return createCard(lists, action.listId, action.card);
        case 'card/updated':
            return mapCards(lists, (cards) =>
                cards.map((card) =>
                    card.id === action.card.id ? action.card : card,
                ),
            );
        case 'card/moved':
            return moveCard(
                lists,
                action.cardId,
                action.toListId,
                action.toIndex,
            );
        case 'card/deleted':
            return mapCards(lists, (cards) =>
                cards.filter((card) => card.id !== action.cardId),
            );
        case 'list/created':
            return lists.some((list) => list.id === action.list.id)
                ? lists
                : [...lists, { ...action.list, cards: [] }];
        case 'list/renamed':
            return lists.map((list) =>
                list.id === action.listId
                    ? { ...list, title: action.title }
                    : list,
            );
        case 'list/moved':
            return moveList(lists, action.listId, action.toIndex);
        case 'list/deleted':
            return lists.filter((list) => list.id !== action.listId);
    }
}

function createCard(
    lists: BoardList[],
    listId: number,
    card: Card,
): BoardList[] {
    const exists = lists.some((list) =>
        list.cards.some((candidate) => candidate.id === card.id),
    );

    if (exists) {
        return lists;
    }

    return lists.map((list) =>
        list.id === listId ? { ...list, cards: [...list.cards, card] } : list,
    );
}

/** Applies `change` to every list, keeping the lists it leaves as they were. */
function mapCards(
    lists: BoardList[],
    change: (cards: Card[]) => Card[],
): BoardList[] {
    return lists.map((list) => {
        const cards = change(list.cards);
        const unchanged =
            cards.length === list.cards.length &&
            cards.every((card, index) => card === list.cards[index]);

        return unchanged ? list : { ...list, cards };
    });
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
