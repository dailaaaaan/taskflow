import { Head, Link } from '@inertiajs/react';
import { KanbanSquare, Settings } from 'lucide-react';
import MemberAvatar from '@/components/teams/member-avatar';
import RoleBadge from '@/components/teams/role-badge';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import { edit, show } from '@/routes/teams';
import type { Member, Team, TeamAbilities } from '@/types';

type Props = {
    team: Team;
    members: Member[];
    can: TeamAbilities;
};

export default function TeamShow({ team, members }: Props) {
    const currentMember = members.find((member) => member.is_current_user);

    return (
        <>
            <Head title={team.name} />

            <div className="flex flex-1 flex-col gap-8 p-4 md:p-6">
                <header className="flex flex-wrap items-center justify-between gap-4">
                    <div className="space-y-2">
                        <div className="flex items-center gap-3">
                            <h1 className="text-2xl font-semibold tracking-tight">
                                {team.name}
                            </h1>
                            {currentMember && (
                                <RoleBadge role={currentMember.role} />
                            )}
                        </div>
                        <div className="flex items-center gap-3">
                            <div className="flex -space-x-2">
                                {members.slice(0, 5).map((member) => (
                                    <MemberAvatar
                                        key={member.id}
                                        user={member.user}
                                        className="ring-2 ring-background"
                                    />
                                ))}
                            </div>
                            <span className="text-sm text-muted-foreground">
                                {members.length}{' '}
                                {members.length === 1 ? 'member' : 'members'}
                            </span>
                        </div>
                    </div>

                    <Button variant="outline" asChild>
                        <Link
                            href={edit(team.id)}
                            data-test="team-settings-link"
                        >
                            <Settings aria-hidden />
                            Team settings
                        </Link>
                    </Button>
                </header>

                <section aria-labelledby="boards-heading" className="space-y-4">
                    <h2 id="boards-heading" className="text-lg font-semibold">
                        Boards
                    </h2>
                    <div className="flex flex-col items-center justify-center gap-3 rounded-xl border border-dashed px-6 py-16 text-center">
                        <div className="flex size-12 items-center justify-center rounded-full bg-muted">
                            <KanbanSquare className="size-6 text-muted-foreground" />
                        </div>
                        <h3 className="font-medium">No boards yet</h3>
                        <p className="max-w-sm text-sm text-muted-foreground">
                            Boards created by this team will appear here.
                        </p>
                    </div>
                </section>
            </div>
        </>
    );
}

TeamShow.layout = ({ team }: { team: Team }) => ({
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: team.name, href: show(team.id) },
    ],
});
