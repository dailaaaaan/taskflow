import { Head, Link } from '@inertiajs/react';
import { KanbanSquare, Users } from 'lucide-react';
import Heading from '@/components/heading';
import CreateTeamDialog from '@/components/teams/create-team-dialog';
import RoleBadge from '@/components/teams/role-badge';
import { pluralize } from '@/lib/utils';
import { dashboard } from '@/routes';
import { show } from '@/routes/teams';
import type { Team } from '@/types';

export default function Dashboard({ teams }: { teams: Team[] }) {
    return (
        <>
            <Head title="Dashboard" />
            <h1 className="sr-only">Dashboard</h1>

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Your teams"
                        description="Teams you belong to and the boards they share."
                    />
                    {teams.length > 0 && <CreateTeamDialog />}
                </div>

                {teams.length === 0 ? (
                    <div className="flex flex-col items-center justify-center gap-4 rounded-xl border border-dashed px-6 py-16 text-center">
                        <div className="flex size-12 items-center justify-center rounded-full bg-muted">
                            <Users className="size-6 text-muted-foreground" />
                        </div>
                        <div className="space-y-1">
                            <h3 className="font-medium">No teams yet</h3>
                            <p className="max-w-sm text-sm text-muted-foreground">
                                Create a team to start organising work on
                                boards, or ask a teammate for an invite link.
                            </p>
                        </div>
                        <CreateTeamDialog />
                    </div>
                ) : (
                    <ul className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        {teams.map((team) => (
                            <li key={team.id}>
                                <Link
                                    href={show(team.id)}
                                    className="group flex h-full flex-col gap-4 rounded-xl border bg-card p-5 transition-colors hover:border-primary/40 hover:bg-accent/40 focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                                >
                                    <div className="flex items-start justify-between gap-3">
                                        <h3 className="font-semibold group-hover:underline">
                                            {team.name}
                                        </h3>
                                        {team.role && (
                                            <RoleBadge role={team.role} />
                                        )}
                                    </div>
                                    <div className="mt-auto flex gap-4 text-sm text-muted-foreground">
                                        <span className="inline-flex items-center gap-1.5">
                                            <Users
                                                className="size-4"
                                                aria-hidden
                                            />
                                            {pluralize(
                                                team.members_count ?? 0,
                                                'member',
                                            )}
                                        </span>
                                        <span className="inline-flex items-center gap-1.5">
                                            <KanbanSquare
                                                className="size-4"
                                                aria-hidden
                                            />
                                            {pluralize(
                                                team.boards_count ?? 0,
                                                'board',
                                            )}
                                        </span>
                                    </div>
                                </Link>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
