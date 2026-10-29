import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/hooks/use-initials';
import { cn } from '@/lib/utils';
import type { Member } from '@/types';

export default function MemberAvatar({
    user,
    className,
}: {
    user: Member['user'];
    className?: string;
}) {
    const getInitials = useInitials();

    return (
        <Avatar
            className={cn('size-8 overflow-hidden rounded-full', className)}
        >
            <AvatarImage src={user.avatar ?? undefined} alt={user.name} />
            <AvatarFallback className="rounded-full bg-neutral-200 text-xs text-black dark:bg-neutral-700 dark:text-white">
                {getInitials(user.name)}
            </AvatarFallback>
        </Avatar>
    );
}
