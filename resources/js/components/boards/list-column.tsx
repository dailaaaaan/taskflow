import { Form, router } from '@inertiajs/react';
import { Pencil, Trash2, X } from 'lucide-react';
import { useState } from 'react';
import ListController from '@/actions/App/Http/Controllers/Boards/ListController';
import AddCardForm from '@/components/boards/add-card-form';
import CardItem from '@/components/boards/card-item';
import ConfirmDialog from '@/components/confirm-dialog';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { pluralize } from '@/lib/utils';
import type { Board, BoardList } from '@/types';

type Props = {
    board: Board;
    list: BoardList;
    canEdit: boolean;
    onOpenCard: (cardId: number) => void;
};

export default function ListColumn({
    board,
    list,
    canEdit,
    onOpenCard,
}: Props) {
    return (
        <section
            aria-label={list.title}
            className="flex w-72 shrink-0 flex-col gap-2 rounded-xl bg-muted/60 p-2"
            data-test="list-column"
        >
            <ListHeader board={board} list={list} canEdit={canEdit} />

            {list.cards.length > 0 && (
                <ol className="flex flex-col gap-2">
                    {list.cards.map((card) => (
                        <li key={card.id}>
                            <CardItem
                                card={card}
                                onOpen={() => onOpenCard(card.id)}
                            />
                        </li>
                    ))}
                </ol>
            )}

            {canEdit && <AddCardForm board={board} list={list} />}
        </section>
    );
}

function ListHeader({
    board,
    list,
    canEdit,
}: {
    board: Board;
    list: BoardList;
    canEdit: boolean;
}) {
    const [renaming, setRenaming] = useState(false);

    if (renaming) {
        return (
            <Form
                {...ListController.update.form({
                    board: board.id,
                    list: list.id,
                })}
                options={{ preserveScroll: true }}
                onSuccess={() => setRenaming(false)}
                className="space-y-1"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="flex items-center gap-1">
                            <Input
                                name="title"
                                aria-label="List title"
                                defaultValue={list.title}
                                required
                                maxLength={100}
                                autoFocus
                                className="h-8"
                                onFocus={(event) =>
                                    event.currentTarget.select()
                                }
                                onKeyDown={(event) => {
                                    if (event.key === 'Escape') {
                                        setRenaming(false);
                                    }
                                }}
                            />
                            <Button
                                type="submit"
                                size="sm"
                                disabled={processing}
                            >
                                Save
                            </Button>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                className="size-8 shrink-0"
                                aria-label="Cancel renaming"
                                onClick={() => setRenaming(false)}
                            >
                                <X />
                            </Button>
                        </div>
                        <InputError message={errors.title} />
                    </>
                )}
            </Form>
        );
    }

    return (
        <div className="flex items-center gap-1">
            <h2 className="min-w-0 flex-1 truncate px-2 text-sm font-semibold">
                {list.title}
            </h2>
            <span className="px-1 text-xs text-muted-foreground tabular-nums">
                <span className="sr-only">Cards: </span>
                {list.cards.length}
            </span>
            {canEdit && (
                <>
                    <Button
                        variant="ghost"
                        size="icon"
                        className="size-7"
                        aria-label={`Rename list ${list.title}`}
                        onClick={() => setRenaming(true)}
                    >
                        <Pencil />
                    </Button>
                    <ConfirmDialog
                        trigger={
                            <Button
                                variant="ghost"
                                size="icon"
                                className="size-7"
                                aria-label={`Delete list ${list.title}`}
                            >
                                <Trash2 />
                            </Button>
                        }
                        title={`Delete ${list.title}?`}
                        description={`The list and its ${pluralize(list.cards.length, 'card')} will be deleted. This cannot be undone.`}
                        confirmLabel="Delete list"
                        onConfirm={() =>
                            router.delete(
                                ListController.destroy({
                                    board: board.id,
                                    list: list.id,
                                }),
                                { preserveScroll: true },
                            )
                        }
                    />
                </>
            )}
        </div>
    );
}
