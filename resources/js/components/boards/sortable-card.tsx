import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import CardItem from '@/components/boards/card-item';
import DragHandle from '@/components/boards/drag-handle';
import { cardDndId } from '@/lib/board-dnd';
import type { DragData } from '@/lib/board-dnd';
import { cn } from '@/lib/utils';
import type { Card } from '@/types';

type Props = {
    card: Card;
    listId: number;
    canEdit: boolean;
    onOpen: () => void;
};

/**
 * A card that can be dragged with the mouse or touch from anywhere on it, or
 * with the keyboard from its handle. A click without movement still opens it.
 */
export default function SortableCard({ card, listId, canEdit, onOpen }: Props) {
    const {
        attributes,
        listeners,
        setNodeRef,
        setActivatorNodeRef,
        transform,
        transition,
        isDragging,
    } = useSortable({
        id: cardDndId(card.id),
        data: { type: 'card', cardId: card.id, listId } satisfies DragData,
        disabled: !canEdit,
    });

    return (
        <div
            ref={setNodeRef}
            style={{ transform: CSS.Translate.toString(transform), transition }}
            className={cn(
                'group/card relative touch-manipulation',
                // The original stays in place as a placeholder under the overlay.
                isDragging && 'opacity-40',
            )}
            {...listeners}
        >
            <CardItem
                card={card}
                onOpen={onOpen}
                className={cn(canEdit && 'pr-10')}
            />
            {canEdit && (
                <DragHandle
                    label={`Move card ${card.title}`}
                    handleRef={setActivatorNodeRef}
                    attributes={attributes}
                    className="absolute top-2 right-2 opacity-0 group-hover/card:opacity-100"
                />
            )}
        </div>
    );
}
