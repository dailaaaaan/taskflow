import { MousePointer2 } from 'lucide-react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

const cardClasses =
    'rounded-lg border bg-card p-3 text-left text-xs font-medium shadow-xs sm:text-sm';

function PreviewCard({ title, tag }: { title: string; tag?: string }) {
    return (
        <div className={cardClasses}>
            {title}
            {tag && (
                <span className="mt-2 block text-xs font-normal text-muted-foreground">
                    {tag}
                </span>
            )}
        </div>
    );
}

function PreviewList({
    title,
    className,
    children,
}: {
    title: string;
    className?: string;
    children: ReactNode;
}) {
    return (
        <div
            className={cn(
                'flex flex-col gap-2 rounded-xl bg-muted/70 p-2',
                className,
            )}
        >
            <p className="px-1 py-0.5 text-xs font-semibold sm:text-sm">
                {title}
            </p>
            {children}
        </div>
    );
}

function Viewer({
    initial,
    className,
}: {
    initial: string;
    className: string;
}) {
    return (
        <span
            className={cn(
                'flex size-7 items-center justify-center rounded-full text-xs font-medium text-white ring-2 ring-background',
                className,
            )}
        >
            {initial}
        </span>
    );
}

/**
 * A static picture of the product. One card slides into the next list once, so
 * the movement stops by itself and nobody needs a control to pause it; with
 * reduced motion the card simply waits above the empty slot.
 */
export default function BoardPreview() {
    return (
        <div
            role="img"
            aria-label="A board with the lists To do, In progress and Done. Sam, another team member, is moving a card to In progress."
            className="rounded-2xl border bg-card p-3 shadow-sm sm:p-4"
        >
            <div className="mb-3 flex items-center justify-between gap-3 px-1">
                <div className="flex min-w-0 items-center gap-2">
                    <span className="size-2.5 shrink-0 rounded-full bg-indigo-500" />
                    <span className="truncate text-sm font-semibold">
                        Product launch
                    </span>
                </div>
                <div className="flex -space-x-1.5">
                    <Viewer initial="S" className="bg-indigo-600" />
                    <Viewer initial="A" className="bg-emerald-700" />
                </div>
            </div>

            <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
                <PreviewList title="To do">
                    <div
                        className={cn(
                            cardClasses,
                            'relative z-10 motion-safe:animate-[landing-card-move_1.2s_cubic-bezier(0.65,0,0.35,1)_1.2s_both]',
                        )}
                    >
                        Prepare the launch email
                        <span className="absolute -top-2.5 -right-2 inline-flex items-center gap-1 rounded-full bg-indigo-600 px-2 py-0.5 text-[11px] font-medium text-white">
                            <MousePointer2 className="size-3" />
                            Sam
                        </span>
                    </div>
                    <PreviewCard title="Record the demo video" />
                    <PreviewCard title="Translate the help pages" />
                </PreviewList>

                <PreviewList title="In progress">
                    <div className="rounded-lg border border-dashed border-muted-foreground/40 p-3 text-xs sm:text-sm">
                        <span className="invisible">
                            Prepare the launch email
                        </span>
                    </div>
                    <PreviewCard
                        title="Design the pricing page"
                        tag="Due Nov 12"
                    />
                </PreviewList>

                <PreviewList title="Done" className="max-sm:hidden">
                    <PreviewCard title="Set up the beta group" />
                    <PreviewCard title="Write the press kit" />
                </PreviewList>
            </div>
        </div>
    );
}
