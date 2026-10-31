import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

/** Below this, a skeleton would only be a flash between two pages. */
const SHOW_AFTER_MS = 250;

/**
 * True while a navigation to another page is taking long. Writes, partial
 * reloads and link prefetches leave the current page on screen, so they do
 * not count.
 */
export function usePendingVisit(): boolean {
    const [pending, setPending] = useState(false);

    useEffect(() => {
        let timer: ReturnType<typeof setTimeout> | undefined;

        const stopListeningToStart = router.on('start', ({ detail }) => {
            const { method, only, prefetch } = detail.visit;

            if (method === 'get' && only.length === 0 && !prefetch) {
                timer = setTimeout(() => setPending(true), SHOW_AFTER_MS);
            }
        });

        const stopListeningToFinish = router.on('finish', ({ detail }) => {
            if (detail.visit.prefetch) {
                return;
            }

            clearTimeout(timer);
            setPending(false);
        });

        return () => {
            clearTimeout(timer);
            stopListeningToStart();
            stopListeningToFinish();
        };
    }, []);

    return pending;
}
