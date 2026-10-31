import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

type Props = {
    icon: LucideIcon;
    title: string;
    description: string;
    /** The next step, usually the button that fills this empty space. */
    children?: ReactNode;
    className?: string;
};

export default function EmptyState({
    icon: Icon,
    title,
    description,
    children,
    className,
}: Props) {
    return (
        <div
            className={cn(
                'flex flex-col items-center justify-center gap-4 rounded-xl border border-dashed px-6 py-16 text-center',
                className,
            )}
        >
            <div className="flex size-12 items-center justify-center rounded-full bg-muted">
                <Icon className="size-6 text-muted-foreground" aria-hidden />
            </div>
            <div className="space-y-1">
                <h3 className="font-medium">{title}</h3>
                <p className="max-w-sm text-sm text-muted-foreground">
                    {description}
                </p>
            </div>
            {children}
        </div>
    );
}
