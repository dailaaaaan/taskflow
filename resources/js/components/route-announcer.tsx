import { useEffect, useState } from 'react';

/**
 * Inertia changes pages without a browser navigation, so screen readers would
 * stay silent. The live region reads the title of each new page; it starts
 * empty because a full page load is already announced by the browser.
 */
export default function RouteAnnouncer() {
    const [title, setTitle] = useState('');

    useEffect(() => {
        // <Head> replaces the <title> element, so the whole head is observed.
        const observer = new MutationObserver(() => setTitle(document.title));

        observer.observe(document.head, { childList: true, subtree: true });

        return () => observer.disconnect();
    }, []);

    return (
        <div role="status" className="sr-only" data-test="route-announcer">
            {title}
        </div>
    );
}
