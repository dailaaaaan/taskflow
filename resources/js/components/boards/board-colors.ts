import type { BoardColor } from '@/types';

/** Same order as App\Enums\BoardColor. */
export const boardColors: BoardColor[] = [
    'indigo',
    'emerald',
    'amber',
    'rose',
    'sky',
    'violet',
];

export const boardColorLabels: Record<BoardColor, string> = {
    indigo: 'Indigo',
    emerald: 'Emerald',
    amber: 'Amber',
    rose: 'Rose',
    sky: 'Sky',
    violet: 'Violet',
};

/**
 * Full class names, never built with string concatenation: Tailwind only
 * generates the classes it can find literally in the source.
 */
export const boardColorClasses: Record<BoardColor, string> = {
    indigo: 'bg-indigo-500',
    emerald: 'bg-emerald-500',
    amber: 'bg-amber-500',
    rose: 'bg-rose-500',
    sky: 'bg-sky-500',
    violet: 'bg-violet-500',
};
