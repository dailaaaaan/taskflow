import type {
    DraggableAttributes,
    DraggableSyntheticListeners,
} from '@dnd-kit/core';
import {
    SortableContext,
    useSortable,
    verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { Form, router } from '@inertiajs/react';
import { Pencil, Trash2, X } from 'lucide-react';
import { useState } from 'react';
import type { Ref } from 'react';
import ListController from '@/actions/App/Http/Controllers/Boards/ListController';
import AddCardForm from '@/components/boards/add-card-form';
import { CardPreview } from '@/components/boards/card-item';
import DragHandle from '@/components/boards/drag-handle';
import SortableCard from '@/components/boards/sortable-card';
import ConfirmDialog from '@/components/confirm-dialog';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { cardDndId, listDndId } from '@/lib/board-dnd';
import type { DragData } from '@/lib/board-dnd';
import { cn, pluralize } from '@/lib/utils';
import type { Board, BoardList } from '@/types';

type Props = {
    board: Board;
    list: BoardList;
    canEdit: boolean;
    onOpenCard: (cardId: number) => void;
};

/** What the header needs to start dragging the whole list. */
type ListDrag = {
    listeners: DraggableSyntheticListeners;
    attributes: DraggableAttributes;
    handleRef: Ref<HTMLButtonElement>;
};

/**
 * A sortable column. It is also the drop zone for cards, so a card can be
 * dropped into an empty list or at the end of a long one.
 */
export default function ListColumn({
    board,
    list,
    canEdit,
    onOpenCard,
}: Props) {
    const {
        attributes,
        listeners,
        setNodeRef,
        setActivatorNodeRef,
        transform,
        transition,
        isDragging,
    } = useSortable({
        id: listDndId(list.id),
        data: { type: 'list', listId: list.id } satisfies DragData,
        disabled: !canEdit,
    });

    return (
        <section
            ref={setNodeRef}
            style={{ transform: CSS.Translate.toString(transform), transition }}
            aria-label={list.title}
            className={cn(
                'flex w-72 shrink-0 flex-col gap-2 rounded-xl bg-muted/60 p-2',
                isDragging && 'opacity-40',
            )}
            data-test="list-column"
        >
            <ListHeader
                board={board}
                list={list}
                canEdit={canEdit}
                drag={
                    canEdit
                        ? {
                              listeners,
                              attributes,
                              handleRef: setActivatorNodeRef,
                          }
                        : null
                }
            />

            <SortableContext
                items={list.cards.map((card) => cardDndId(card.id))}
                strategy={verticalListSortingStrategy}
            >
                {list.cards.length > 0 && (
                    <ol className="flex flex-col gap-2">
                        {list.cards.map((card) => (
                            <li key={card.id}>
                                <SortableCard
                                    card={card}
                                    listId={list.id}
                                    canEdit={canEdit}
                                    onOpen={() => onOpenCard(card.id)}
                                />
                            </li>
                        ))}
                    </ol>
                )}
            </SortableContext>

            {canEdit && <AddCardForm board={board} list={list} />}
        </section>
    );
}

/** Static copy of a list that follows the pointer while it is dragged. */
export function ListPreview({ list }: { list: BoardList }) {
    return (
        <div className="flex w-72 cursor-grabbing flex-col gap-2 rounded-xl bg-muted p-2 shadow-lg ring-2 ring-primary/30">
            <p className="px-2 py-1 text-sm font-semibold">{list.title}</p>
            {list.cards.map((card) => (
                <CardPreview key={card.id} card={card} />
            ))}
        </div>
    );
}

function ListHeader({
    board,
    list,
    canEdit,
    drag,
}: {
    board: Board;
    list: BoardList;
    canEdit: boolean;
    drag: ListDrag | null;
}) {
    const [renaming, setRenaming] = useState(false);

    if (renaming) {
        return (
            <Form
                {...ListController.update.form({
                    board: board.id,
                    list: list.id,
                })}
                options={{ preserveScroll: true }}
                onSuccess={() => setRenaming(false)}
                className="space-y-1"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="flex items-center gap-1">
                            <Input
                                name="title"
                                aria-label="List title"
                                defaultValue={list.title}
                                required
                                maxLength={100}
                                autoFocus
                                className="h-8"
                                onFocus={(event) =>
                                    event.currentTarget.select()
                                }
                                onKeyDown={(event) => {
                                    if (event.key === 'Escape') {
                                        setRenaming(false);
                                    }
                                }}
                            />
                            <Button
                                type="submit"
                                size="sm"
                                disabled={processing}
                            >
                                Save
                            </Button>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                className="size-8 shrink-0"
                                aria-label="Cancel renaming"
                                onClick={() => setRenaming(false)}
                            >
                                <X />
                            </Button>
                        </div>
                        <InputError message={errors.title} />
                    </>
                )}
            </Form>
        );
    }

    return (
        <div className="flex items-center gap-1">
            {drag && (
                <DragHandle
                    label={`Move list ${list.title}`}
                    handleRef={drag.handleRef}
                    attributes={drag.attributes}
                    listeners={drag.listeners}
                />
            )}
            {/* The title is the pointer grip of the list; not while renaming, so text can be selected. */}
            <h2
                className={cn(
                    'min-w-0 flex-1 truncate px-1 text-sm font-semibold',
                    drag && 'cursor-grab touch-manipulation',
                )}
                {...drag?.listeners}
            >
                {list.title}
            </h2>
            <span className="px-1 text-xs text-muted-foreground tabular-nums">
                <span className="sr-only">Cards: </span>
                {list.cards.length}
            </span>
            {canEdit && (
                <>
                    <Button
                        variant="ghost"
                        size="icon"
                        className="size-7"
                        aria-label={`Rename list ${list.title}`}
                        onClick={() => setRenaming(true)}
                    >
                        <Pencil />
                    </Button>
                    <ConfirmDialog
                        trigger={
                            <Button
                                variant="ghost"
                                size="icon"
                                className="size-7"
                                aria-label={`Delete list ${list.title}`}
                            >
                                <Trash2 />
                            </Button>
                        }
                        title={`Delete ${list.title}?`}
                        description={`The list and its ${pluralize(list.cards.length, 'card')} will be deleted. This cannot be undone.`}
                        confirmLabel="Delete list"
                        onConfirm={() =>
                            router.delete(
                                ListController.destroy({
                                    board: board.id,
                                    list: list.id,
                                }),
                                { preserveScroll: true },
                            )
                        }
                    />
                </>
            )}
        </div>
    );
}
