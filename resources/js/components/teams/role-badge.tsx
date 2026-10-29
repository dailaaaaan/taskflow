import { ShieldCheck } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import type { TeamRole } from '@/types';

export const roleLabels: Record<TeamRole, string> = {
    admin: 'Admin',
    member: 'Member',
};

export default function RoleBadge({ role }: { role: TeamRole }) {
    return (
        <Badge variant={role === 'admin' ? 'default' : 'secondary'}>
            {role === 'admin' && <ShieldCheck aria-hidden />}
            {roleLabels[role]}
        </Badge>
    );
}
