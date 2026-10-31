import { router } from '@inertiajs/react';
import { toast } from 'sonner';

const messages: Record<number, string> = {
    403: "You don't have permission to do that.",
    404: 'That no longer exists. Someone else may have deleted it.',
    419: 'Your session expired. Reload the page and try again.',
    429: 'Too many requests. Please wait a minute and try again.',
};

export function failedRequestMessage(status: number): string {
    return messages[status] ?? 'Something went wrong. Please try again.';
}

/**
 * Failed writes end in a toast instead of Inertia's error modal, so a clash
 * with another user on the board does not take over the screen. Failed page
 * visits are not touched: the server answers those with the errors/show page.
 */
export function notifyFailedVisits(): void {
    router.on('httpException', (event) => {
        const { headers, status } = event.detail.response;

        if (headers['x-inertia']) {
            return;
        }

        // Keeps the stack trace of Laravel's debug screen while developing.
        if (import.meta.env.DEV && status >= 500) {
            return;
        }

        event.preventDefault();
        toast.error(failedRequestMessage(status));
    });

    router.on('networkError', (event) => {
        event.preventDefault();
        toast.error(
            'The server could not be reached. Check your connection and try again.',
        );
    });
}
