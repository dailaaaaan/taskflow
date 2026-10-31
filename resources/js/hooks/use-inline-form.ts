import { useRef, useState } from 'react';
import { flushSync } from 'react-dom';

/**
 * State of a form that takes the place of the button that opens it. Closing it
 * removes the focused input, so focus returns to the button: otherwise the
 * browser drops it and keyboard users start again from the top of the page.
 */
export function useInlineForm() {
    const [isOpen, setIsOpen] = useState(false);
    const openButton = useRef<HTMLButtonElement>(null);

    return {
        isOpen,
        openButton,
        open: () => setIsOpen(true),
        close: () => {
            flushSync(() => setIsOpen(false));
            openButton.current?.focus();
        },
    };
}
