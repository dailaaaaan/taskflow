import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import PageSkeleton from '@/components/page-skeleton';
import { usePendingVisit } from '@/hooks/use-pending-visit';
import { cn } from '@/lib/utils';
import type { AppLayoutProps } from '@/types';

export default function AppSidebarLayout({
    children,
    breadcrumbs = [],
}: AppLayoutProps) {
    const pendingVisit = usePendingVisit();

    return (
        <AppShell variant="sidebar">
            <a
                href="#main-content"
                className="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:rounded-md focus:bg-background focus:px-3 focus:py-2 focus:text-sm focus:font-medium focus:shadow-md focus:ring-[3px] focus:ring-ring/50"
                data-test="skip-link"
            >
                Skip to main content
            </a>
            <AppSidebar />
            <AppContent
                id="main-content"
                tabIndex={-1}
                variant="sidebar"
                className="min-w-0 overflow-x-clip outline-none"
            >
                <AppSidebarHeader breadcrumbs={breadcrumbs} />
                {pendingVisit && <PageSkeleton />}
                {/* Hidden, not removed: a failed visit must not lose the page state. */}
                <div className={cn('contents', pendingVisit && 'hidden')}>
                    {children}
                </div>
            </AppContent>
        </AppShell>
    );
}
