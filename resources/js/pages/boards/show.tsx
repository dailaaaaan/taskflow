import { Head, router } from '@inertiajs/react';
import { Pencil, Trash2 } from 'lucide-react';
import { useState } from 'react';
import BoardController from '@/actions/App/Http/Controllers/Boards/BoardController';
import AddListForm from '@/components/boards/add-list-form';
import { boardColorClasses } from '@/components/boards/board-colors';
import BoardFormDialog from '@/components/boards/board-form-dialog';
import CardDialog from '@/components/boards/card-dialog';
import ListColumn from '@/components/boards/list-column';
import ConfirmDialog from '@/components/confirm-dialog';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import { show as showBoard } from '@/routes/boards';
import { show as showTeam } from '@/routes/teams';
import type { Board, BoardAbilities, BoardList, Member, Team } from '@/types';

type Props = {
    team: Team;
    board: Board;
    lists: BoardList[];
    members: Member[];
    can: BoardAbilities;
};

export default function BoardShow({ board, lists, members, can }: Props) {
    // Only the id is kept, so the modal always shows the latest server data.
    const [openCardId, setOpenCardId] = useState<number | null>(null);
    const openList = lists.find((list) =>
        list.cards.some((card) => card.id === openCardId),
    );
    const openCard =
        openList?.cards.find((card) => card.id === openCardId) ?? null;

    return (
        <>
            <Head title={board.title} />

            <div className="flex min-h-0 flex-1 flex-col">
                <header className="flex flex-wrap items-center justify-between gap-3 border-b px-4 py-3 md:px-6">
                    <div className="flex min-w-0 items-center gap-3">
                        <span
                            className={cn(
                                'size-3 shrink-0 rounded-full',
                                boardColorClasses[board.color],
                            )}
                            aria-hidden
                        />
                        <h1 className="truncate text-xl font-semibold tracking-tight">
                            {board.title}
                        </h1>
                    </div>

                    {(can.update || can.delete) && (
                        <div className="flex gap-2">
                            {can.update && (
                                <BoardFormDialog
                                    trigger={
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            data-test="edit-board-button"
                                        >
                                            <Pencil aria-hidden />
                                            Edit board
                                        </Button>
                                    }
                                    title="Edit board"
                                    description="Rename the board or change its color."
                                    form={BoardController.update.form(board.id)}
                                    submitLabel="Save"
                                    board={board}
                                />
                            )}
                            {can.delete && (
                                <ConfirmDialog
                                    trigger={
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            data-test="delete-board-button"
                                        >
                                            <Trash2 aria-hidden />
                                            Delete
                                        </Button>
                                    }
                                    title={`Delete ${board.title}?`}
                                    description="All of its lists and cards will be deleted. This cannot be undone."
                                    confirmLabel="Delete board"
                                    onConfirm={() =>
                                        router.delete(
                                            BoardController.destroy(board.id),
                                        )
                                    }
                                />
                            )}
                        </div>
                    )}
                </header>

                <div
                    className="flex flex-1 items-start gap-4 overflow-x-auto p-4 md:p-6"
                    data-test="board-lists"
                >
                    {lists.map((list) => (
                        <ListColumn
                            key={list.id}
                            board={board}
                            list={list}
                            canEdit={can.collaborate}
                            onOpenCard={setOpenCardId}
                        />
                    ))}

                    {can.collaborate && <AddListForm board={board} />}

                    {lists.length === 0 && !can.collaborate && (
                        <p className="text-sm text-muted-foreground">
                            This board has no lists yet.
                        </p>
                    )}
                </div>
            </div>

            <CardDialog
                board={board}
                card={openCard}
                listTitle={openList?.title ?? ''}
                members={members}
                canEdit={can.collaborate}
                onClose={() => setOpenCardId(null)}
            />
        </>
    );
}

BoardShow.layout = ({ team, board }: { team: Team; board: Board }) => ({
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: team.name, href: showTeam(team.id) },
        { title: board.title, href: showBoard(board.id) },
    ],
});
