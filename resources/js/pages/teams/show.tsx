import { Head, Link } from '@inertiajs/react';
import { KanbanSquare, Plus, Settings } from 'lucide-react';
import BoardController from '@/actions/App/Http/Controllers/Boards/BoardController';
import { boardColorClasses } from '@/components/boards/board-colors';
import BoardFormDialog from '@/components/boards/board-form-dialog';
import MemberAvatar from '@/components/teams/member-avatar';
import RoleBadge from '@/components/teams/role-badge';
import { Button } from '@/components/ui/button';
import { cn, pluralize } from '@/lib/utils';
import { dashboard } from '@/routes';
import { show as showBoard } from '@/routes/boards';
import { edit, show } from '@/routes/teams';
import type { Board, Member, Team, TeamAbilities } from '@/types';

type Props = {
    team: Team;
    members: Member[];
    boards: Board[];
    can: TeamAbilities;
};

export default function TeamShow({ team, members, boards, can }: Props) {
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
                    <div className="flex flex-wrap items-center justify-between gap-4">
                        <h2
                            id="boards-heading"
                            className="text-lg font-semibold"
                        >
                            Boards
                        </h2>
                        {can.createBoard && boards.length > 0 && (
                            <CreateBoardDialog team={team} />
                        )}
                    </div>

                    {boards.length === 0 ? (
                        <div className="flex flex-col items-center justify-center gap-4 rounded-xl border border-dashed px-6 py-16 text-center">
                            <div className="flex size-12 items-center justify-center rounded-full bg-muted">
                                <KanbanSquare className="size-6 text-muted-foreground" />
                            </div>
                            <div className="space-y-1">
                                <h3 className="font-medium">No boards yet</h3>
                                <p className="max-w-sm text-sm text-muted-foreground">
                                    Boards hold the lists and cards your team
                                    works on.
                                </p>
                            </div>
                            {can.createBoard && (
                                <CreateBoardDialog team={team} />
                            )}
                        </div>
                    ) : (
                        <ul className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                            {boards.map((board) => (
                                <li key={board.id}>
                                    <BoardLink board={board} />
                                </li>
                            ))}
                        </ul>
                    )}
                </section>
            </div>
        </>
    );
}

function CreateBoardDialog({ team }: { team: Team }) {
    return (
        <BoardFormDialog
            trigger={
                <Button data-test="create-board-button">
                    <Plus aria-hidden />
                    New board
                </Button>
            }
            title="Create a board"
            description={`Every member of ${team.name} will be able to work on it.`}
            form={BoardController.store.form(team.id)}
            submitLabel="Create board"
        />
    );
}

function BoardLink({ board }: { board: Board }) {
    return (
        <Link
            href={showBoard(board.id)}
            className="group flex h-full flex-col overflow-hidden rounded-xl border bg-card transition-colors hover:border-primary/40 hover:bg-accent/40 focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
        >
            <span
                className={cn('h-1.5', boardColorClasses[board.color])}
                aria-hidden
            />
            <span className="flex flex-1 flex-col gap-4 p-5">
                <h3 className="font-semibold group-hover:underline">
                    {board.title}
                </h3>
                <span className="mt-auto text-sm text-muted-foreground">
                    {pluralize(board.lists_count ?? 0, 'list')} ·{' '}
                    {pluralize(board.cards_count ?? 0, 'card')}
                </span>
            </span>
        </Link>
    );
}

TeamShow.layout = ({ team }: { team: Team }) => ({
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: team.name, href: show(team.id) },
    ],
});
