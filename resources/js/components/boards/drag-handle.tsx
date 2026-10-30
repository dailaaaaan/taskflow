import type {
    DraggableAttributes,
    DraggableSyntheticListeners,
} from '@dnd-kit/core';
import { GripVertical } from 'lucide-react';
import type { Ref } from 'react';
import { cn } from '@/lib/utils';

type Props = {
    label: string;
    /** `setActivatorNodeRef` from useSortable: only this button starts a keyboard drag. */
    handleRef: Ref<HTMLButtonElement>;
    attributes: DraggableAttributes;
    /** Needed when no ancestor of the handle already has the drag listeners. */
    listeners?: DraggableSyntheticListeners;
    className?: string;
};

/**
 * Keyboard entry point of a drag: Space or Enter picks the item up. Pointer
 * drags start anywhere on the item, so the handle stays discreet until the
 * item is hovered or the handle is focused.
 */
export default function DragHandle({
    label,
    handleRef,
    attributes,
    listeners,
    className,
}: Props) {
    return (
        <button
            type="button"
            ref={handleRef}
            {...attributes}
            {...listeners}
            aria-label={label}
            className={cn(
                'flex size-7 shrink-0 cursor-grab items-center justify-center rounded-md text-muted-foreground transition-opacity hover:bg-accent hover:text-foreground focus-visible:opacity-100 focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none active:cursor-grabbing',
                className,
            )}
            data-test="drag-handle"
        >
            <GripVertical className="size-4" aria-hidden />
        </button>
    );
}
