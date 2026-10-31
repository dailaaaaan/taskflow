import { Skeleton } from '@/components/ui/skeleton';

/** Stands in for the page while a slow navigation is on its way. */
export default function PageSkeleton() {
    return (
        <div
            role="status"
            className="flex flex-1 flex-col gap-6 p-4 md:p-6"
            data-test="page-skeleton"
        >
            <span className="sr-only">Loading…</span>
            <Skeleton className="h-8 w-56" />
            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <Skeleton className="h-36" />
                <Skeleton className="h-36" />
                <Skeleton className="h-36 max-sm:hidden" />
            </div>
        </div>
    );
}
