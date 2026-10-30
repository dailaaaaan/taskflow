import type { BoardList } from '@/types';

/** Stored in dnd-kit's `data` for every draggable and droppable of a board. */
export type DragData =
    | { type: 'card'; cardId: number; listId: number }
    | { type: 'list'; listId: number };

/** Cards and lists share one DndContext, so their ids are prefixed. */
export function cardDndId(id: number): string {
    return `card-${id}`;
}

export function listDndId(id: number): string {
    return `list-${id}`;
}

/** The list that currently holds the card. */
export function listOfCard(
    lists: BoardList[],
    cardId: number,
): BoardList | undefined {
    return lists.find((list) => list.cards.some((card) => card.id === cardId));
}

/**
 * Where a dragged card lands when it is released over `over`, as the
 * `toListId` and `toIndex` of a `card/moved` action. Over a card it takes
 * that card's slot, which is what sortable lists preview; over a list it goes
 * to the end.
 */
export function cardDropTarget(
    lists: BoardList[],
    cardId: number,
    over: DragData,
): { listId: number; index: number } | null {
    if (over.type === 'card') {
        const list = listOfCard(lists, over.cardId);

        return list
            ? {
                  listId: list.id,
                  index: list.cards.findIndex(
                      (card) => card.id === over.cardId,
                  ),
              }
            : null;
    }

    const list = lists.find((candidate) => candidate.id === over.listId);

    return list
        ? {
              listId: list.id,
              index: list.cards.filter((card) => card.id !== cardId).length,
          }
        : null;
}

/** Whether a drop target is exactly where the card already is. */
export function isCardAt(
    lists: BoardList[],
    cardId: number,
    target: { listId: number; index: number },
): boolean {
    const list = listOfCard(lists, cardId);

    return (
        list?.id === target.listId &&
        list.cards.findIndex((card) => card.id === cardId) === target.index
    );
}

export type ArrowKey = 'ArrowUp' | 'ArrowDown' | 'ArrowLeft' | 'ArrowRight';

type Rect = { top: number; left: number; right: number; bottom: number };

export function isArrowKey(code: string): code is ArrowKey {
    return ['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'].includes(code);
}

/**
 * Where an arrow key takes a dragged item: ← and → to the nearest list
 * entirely on that side, ↑ and ↓ to the nearest card above or below in the
 * same column. Lists only move sideways. `targets` excludes the dragged item.
 */
export function keyboardTarget(
    dragged: DragData['type'],
    from: Rect,
    key: ArrowKey,
    targets: { type: DragData['type']; rect: Rect }[],
): Rect | null {
    const sideways = key === 'ArrowLeft' || key === 'ArrowRight';

    if (dragged === 'list' && !sideways) {
        return null;
    }

    const candidates = targets
        .filter(({ type, rect }) => {
            if (sideways) {
                return (
                    type === 'list' &&
                    (key === 'ArrowLeft'
                        ? rect.right <= from.left
                        : rect.left >= from.right)
                );
            }

            const sameColumn = rect.left < from.right && rect.right > from.left;

            return (
                type === 'card' &&
                sameColumn &&
                (key === 'ArrowUp' ? rect.top < from.top : rect.top > from.top)
            );
        })
        .map(({ rect }) => ({
            rect,
            distance: sideways
                ? Math.abs(rect.left - from.left)
                : Math.abs(rect.top - from.top),
        }))
        .sort((a, b) => a.distance - b.distance);

    return candidates[0]?.rect ?? null;
}

/** Index of the list under `over`, whether a list or one of its cards. */
export function listDropIndex(lists: BoardList[], over: DragData): number {
    const listId =
        over.type === 'card' ? listOfCard(lists, over.cardId)?.id : over.listId;

    return lists.findIndex((list) => list.id === listId);
}
