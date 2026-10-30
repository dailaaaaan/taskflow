import { usePage } from '@inertiajs/react';
import MemberAvatar from '@/components/teams/member-avatar';
import type { Viewer } from '@/types';

const MAX_AVATARS = 4;

/**
 * The teammates on this board right now. The current user is left out: they
 * already know they are here.
 */
export default function BoardViewers({ viewers }: { viewers: Viewer[] }) {
    const { auth } = usePage().props;
    const others = viewers.filter(({ id }) => id !== auth.user.id);

    if (others.length === 0) {
        return null;
    }

    const label = `Also viewing: ${others.map(({ name }) => name).join(', ')}`;

    return (
        <div
            role="group"
            aria-label={label}
            title={label}
            className="flex shrink-0 items-center -space-x-2"
            data-test="board-viewers"
        >
            {others.slice(0, MAX_AVATARS).map((viewer) => (
                <MemberAvatar
                    key={viewer.id}
                    user={viewer}
                    className="ring-2 ring-background"
                />
            ))}
            {others.length > MAX_AVATARS && (
                <span className="z-10 flex size-8 items-center justify-center rounded-full bg-muted text-xs font-medium ring-2 ring-background">
                    +{others.length - MAX_AVATARS}
                </span>
            )}
        </div>
    );
}
