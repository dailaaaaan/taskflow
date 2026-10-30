import {
    closestCenter,
    closestCorners,
    DndContext,
    DragOverlay,
    KeyboardSensor,
    MeasuringStrategy,
    MouseSensor,
    TouchSensor,
    useSensor,
    useSensors,
} from '@dnd-kit/core';
import type {
    Active,
    Announcements,
    CollisionDetection,
    DragEndEvent,
    DragOverEvent,
    DragStartEvent,
    KeyboardCoordinateGetter,
    Over,
} from '@dnd-kit/core';
import {
    horizontalListSortingStrategy,
    SortableContext,
} from '@dnd-kit/sortable';
import { router } from '@inertiajs/react';
import { useRef, useState } from 'react';
import { toast } from 'sonner';
import CardController from '@/actions/App/Http/Controllers/Boards/CardController';
import ListController from '@/actions/App/Http/Controllers/Boards/ListController';
import AddListForm from '@/components/boards/add-list-form';
import { CardPreview } from '@/components/boards/card-item';
import ListColumn, { ListPreview } from '@/components/boards/list-column';
import {
    cardDropTarget,
    isArrowKey,
    isCardAt,
    keyboardTarget,
    listDndId,
    listDropIndex,
    listOfCard,
} from '@/lib/board-dnd';
import type { DragData } from '@/lib/board-dnd';
import { boardReducer } from '@/lib/board-reducer';
import type { BoardAction } from '@/lib/board-reducer';
import type { Board, BoardList } from '@/types';
import type { RouteDefinition } from '@/wayfinder';

type Props = {
    board: Board;
    lists: BoardList[];
    canEdit: boolean;
    onOpenCard: (cardId: number) => void;
};

/** Reads the DragData that ListColumn and SortableCard attach to dnd-kit items. */
function dragData(
    item: { data: { current?: unknown } } | null,
): DragData | null {
    return (item?.data.current as DragData | undefined) ?? null;
}

/**
 * Lists only collide with lists, so a dragged list is never "over" a card.
 * Cards use the corners of every droppable, which suits stacked columns.
 */
const collisionDetection: CollisionDetection = (args) => {
    if (dragData(args.active)?.type === 'list') {
        return closestCenter({
            ...args,
            droppableContainers: args.droppableContainers.filter(
                (container) => dragData(container)?.type === 'list',
            ),
        });
    }

    return closestCorners(args);
};

/**
 * Arrow keys while dragging with the keyboard. dnd-kit's sortable default
 * looks at every droppable at once (lists and cards), which can pick a card
 * of the same column for →; keyboardTarget keeps the moves predictable.
 */
const keyboardCoordinates: KeyboardCoordinateGetter = (event, { context }) => {
    const { active, collisionRect, droppableRects, droppableContainers } =
        context;
    const dragged = dragData(active);

    if (!isArrowKey(event.code) || !dragged || !collisionRect) {
        return undefined;
    }

    // The arrows drive the drag, not the page scroll.
    event.preventDefault();

    const targets = droppableContainers.getEnabled().flatMap((container) => {
        const data = dragData(container);
        const rect = droppableRects.get(container.id);

        return container.id !== active?.id && data && rect
            ? [{ type: data.type, rect }]
            : [];
    });
    const rect = keyboardTarget(
        dragged.type,
        collisionRect,
        event.code,
        targets,
    );

    return rect ? { x: rect.left, y: rect.top } : undefined;
};

function notifyFailedMove(): void {
    toast.error('The move could not be saved, so the board was restored.');
}

/**
 * Saves a move. Inertia applies the reducer to the props straight away
 * (optimistic update), reloads only `lists` when the server answers, and
 * restores the previous lists by itself if the request fails.
 */
function saveMove(
    action: BoardAction,
    route: RouteDefinition<'patch'>,
    data: Record<string, number>,
): void {
    router
        .optimistic<{ lists: BoardList[] }>((props) => ({
            lists: boardReducer(props.lists, action),
        }))
        .patch(route, data, {
            only: ['lists'],
            preserveScroll: true,
            showProgress: false,
            onError: notifyFailedMove,
            // Returning false replaces Inertia's error modal with the toast.
            onHttpException: () => {
                notifyFailedMove();

                return false;
            },
            onNetworkError: () => {
                notifyFailedMove();

                return false;
            },
        });
}

/**
 * The lists of a board with drag & drop for lists and cards, by mouse, touch
 * or keyboard. Nothing is saved until the drop.
 */
export default function SortableBoard({
    board,
    lists,
    canEdit,
    onOpenCard,
}: Props) {
    // While a card is dragged across lists it is previewed in the new list.
    const [preview, setPreview] = useState<BoardList[] | null>(null);
    const [active, setActive] = useState<DragData | null>(null);
    // Moving a card to another list shifts the layout under the pointer;
    // waiting a frame avoids bouncing between two lists.
    const justMovedAcross = useRef(false);
    const shown = preview ?? lists;

    const sensors = useSensors(
        // A click without movement still opens the card.
        useSensor(MouseSensor, { activationConstraint: { distance: 5 } }),
        // A quick swipe still scrolls the board; press and hold to drag.
        useSensor(TouchSensor, {
            activationConstraint: { delay: 200, tolerance: 6 },
        }),
        useSensor(KeyboardSensor, { coordinateGetter: keyboardCoordinates }),
    );

    function reset(): void {
        setActive(null);
        setPreview(null);
    }

    function handleDragStart({ active }: DragStartEvent): void {
        setActive(dragData(active));
    }

    function handleDragOver({ active, over }: DragOverEvent): void {
        const dragged = dragData(active);
        const target = dragData(over);

        if (dragged?.type !== 'card' || !target || justMovedAcross.current) {
            return;
        }

        const from = listOfCard(shown, dragged.cardId);
        const to = cardDropTarget(shown, dragged.cardId, target);

        // Within one list the sortable animation already previews the order.
        if (!from || !to || from.id === to.listId) {
            return;
        }

        justMovedAcross.current = true;
        requestAnimationFrame(() => {
            justMovedAcross.current = false;
        });

        setPreview(
            boardReducer(shown, {
                type: 'card/moved',
                cardId: dragged.cardId,
                toListId: to.listId,
                toIndex: to.index,
            }),
        );
    }

    function handleDragEnd({ active, over }: DragEndEvent): void {
        const dragged = dragData(active);
        const target = dragData(over);
        reset();

        if (!dragged || !target) {
            return;
        }

        if (dragged.type === 'card') {
            const to = cardDropTarget(shown, dragged.cardId, target);

            if (!to || isCardAt(lists, dragged.cardId, to)) {
                return;
            }

            saveMove(
                {
                    type: 'card/moved',
                    cardId: dragged.cardId,
                    toListId: to.listId,
                    toIndex: to.index,
                },
                CardController.move({ board: board.id, card: dragged.cardId }),
                { list_id: to.listId, index: to.index },
            );

            return;
        }

        const from = lists.findIndex((list) => list.id === dragged.listId);
        const to = listDropIndex(lists, target);

        if (to === -1 || to === from) {
            return;
        }

        saveMove(
            { type: 'list/moved', listId: dragged.listId, toIndex: to },
            ListController.move({ board: board.id, list: dragged.listId }),
            { index: to },
        );
    }

    const describe = (item: Active | Over | null): string => {
        const data = dragData(item);

        if (data?.type === 'card') {
            const list = listOfCard(shown, data.cardId);
            const card = list?.cards.find(({ id }) => id === data.cardId);

            return `card “${card?.title ?? ''}” in list “${list?.title ?? ''}”`;
        }

        const list = shown.find(({ id }) => id === data?.listId);

        return `list “${list?.title ?? ''}”`;
    };

    const announcements: Announcements = {
        onDragStart: ({ active }) => `Picked up ${describe(active)}.`,
        onDragOver: ({ active, over }) => {
            // Right after the pick-up the item is over itself; announcing that
            // would cut off "Picked up…" in screen readers.
            if (over?.id === active.id) {
                return undefined;
            }

            return over
                ? `Moved ${describe(active)} over ${describe(over)}.`
                : `${describe(active)} is no longer over a drop zone.`;
        },
        onDragEnd: ({ active, over }) =>
            over
                ? `Dropped ${describe(active)} on ${describe(over)}.`
                : `Dropped ${describe(active)}.`,
        onDragCancel: ({ active }) =>
            `Moving cancelled. ${describe(active)} returned to its place.`,
    };

    const activeCard =
        active?.type === 'card'
            ? listOfCard(shown, active.cardId)?.cards.find(
                  ({ id }) => id === active.cardId,
              )
            : undefined;
    const activeList =
        active?.type === 'list'
            ? shown.find(({ id }) => id === active.listId)
            : undefined;

    return (
        <DndContext
            id={`board-${board.id}`}
            sensors={sensors}
            collisionDetection={collisionDetection}
            measuring={{ droppable: { strategy: MeasuringStrategy.Always } }}
            accessibility={{
                announcements,
                screenReaderInstructions: {
                    draggable:
                        'To move a card or a list, focus its move handle and press Space or Enter. Use the arrow keys to move it, Space or Enter to drop it, or Escape to cancel.',
                },
            }}
            onDragStart={handleDragStart}
            onDragOver={handleDragOver}
            onDragEnd={handleDragEnd}
            onDragCancel={reset}
        >
            <div
                className="flex flex-1 items-start gap-4 overflow-x-auto p-4 md:p-6"
                data-test="board-lists"
            >
                <SortableContext
                    items={shown.map((list) => listDndId(list.id))}
                    strategy={horizontalListSortingStrategy}
                >
                    {shown.map((list) => (
                        <ListColumn
                            key={list.id}
                            board={board}
                            list={list}
                            canEdit={canEdit}
                            onOpenCard={onOpenCard}
                        />
                    ))}
                </SortableContext>

                {canEdit && <AddListForm board={board} />}

                {shown.length === 0 && !canEdit && (
                    <p className="text-sm text-muted-foreground">
                        This board has no lists yet.
                    </p>
                )}
            </div>

            <DragOverlay>
                {activeCard && <CardPreview card={activeCard} />}
                {activeList && <ListPreview list={activeList} />}
            </DragOverlay>
        </DndContext>
    );
}
