import { AlignLeft, CalendarDays } from 'lucide-react';
import MemberAvatar from '@/components/teams/member-avatar';
import { cn } from '@/lib/utils';
import type { Card } from '@/types';

const cardClasses =
    'w-full rounded-lg border bg-card p-3 text-left text-sm shadow-xs';

/** `2026-11-15` → `Nov 15`, parsed as a local date to avoid timezone shifts. */
function formatDueDate(date: string): string {
    return new Date(`${date}T00:00:00`).toLocaleDateString('en', {
        month: 'short',
        day: 'numeric',
    });
}

/**
 * A card in a list. The whole card is a button that opens the details modal.
 */
export default function CardItem({
    card,
    onOpen,
    className,
}: {
    card: Card;
    onOpen: () => void;
    className?: string;
}) {
    return (
        <button
            type="button"
            onClick={onOpen}
            className={cn(
                cardClasses,
                'transition-colors hover:border-primary/40 focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none',
                className,
            )}
            data-test="card-item"
        >
            <CardContent card={card} />
        </button>
    );
}

/**
 * Static copy of a card that follows the pointer while it is dragged. No
 * rotation or scale: dnd-kit measures this element to find drop targets.
 */
export function CardPreview({ card }: { card: Card }) {
    return (
        <div
            className={cn(
                cardClasses,
                'cursor-grabbing shadow-lg ring-2 ring-primary/30',
            )}
        >
            <CardContent card={card} />
        </div>
    );
}

function CardContent({ card }: { card: Card }) {
    // `en-CA` formats as YYYY-MM-DD in local time, comparable as a string.
    const today = new Date().toLocaleDateString('en-CA');
    const overdue = card.due_date !== null && card.due_date < today;
    const hasDetails =
        card.due_date !== null ||
        card.description !== null ||
        card.assignee !== null;

    return (
        <>
            <span className="block font-medium break-words">{card.title}</span>

            {hasDetails && (
                <span className="mt-2 flex items-center gap-3 text-xs text-muted-foreground">
                    {card.due_date !== null && (
                        <span
                            className={cn(
                                'inline-flex items-center gap-1',
                                overdue &&
                                    'font-medium text-red-600 dark:text-red-400',
                            )}
                        >
                            <CalendarDays className="size-3.5" aria-hidden />
                            <span className="sr-only">
                                {overdue ? 'Overdue, was due' : 'Due'}
                            </span>
                            {formatDueDate(card.due_date)}
                        </span>
                    )}
                    {card.description !== null && (
                        <span className="inline-flex">
                            <AlignLeft className="size-3.5" aria-hidden />
                            <span className="sr-only">Has a description</span>
                        </span>
                    )}
                    {card.assignee !== null && (
                        <span className="ml-auto inline-flex">
                            <span className="sr-only">Assigned to</span>
                            <MemberAvatar
                                user={card.assignee}
                                className="size-6"
                            />
                        </span>
                    )}
                </span>
            )}
        </>
    );
}
