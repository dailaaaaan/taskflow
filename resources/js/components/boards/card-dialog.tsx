import { Form, router } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import { useState } from 'react';
import CardController from '@/actions/App/Http/Controllers/Boards/CardController';
import ConfirmDialog from '@/components/confirm-dialog';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import type { Board, Card, Member } from '@/types';

/** Radix Select items cannot have an empty value. */
const UNASSIGNED = 'unassigned';

type Props = {
    board: Board;
    /** The open card, or null when the modal is closed. */
    card: Card | null;
    listTitle: string;
    members: Member[];
    canEdit: boolean;
    onClose: () => void;
};

export default function CardDialog({
    board,
    card,
    listTitle,
    members,
    canEdit,
    onClose,
}: Props) {
    return (
        <Dialog
            open={card !== null}
            onOpenChange={(open) => {
                if (!open) {
                    onClose();
                }
            }}
        >
            <DialogContent className="sm:max-w-lg">
                {card !== null && (
                    // The key resets the form's defaults when another card opens.
                    <CardDetailsForm
                        key={card.id}
                        board={board}
                        card={card}
                        listTitle={listTitle}
                        members={members}
                        canEdit={canEdit}
                        onClose={onClose}
                    />
                )}
            </DialogContent>
        </Dialog>
    );
}

function CardDetailsForm({
    board,
    card,
    listTitle,
    members,
    canEdit,
    onClose,
}: Omit<Props, 'card'> & { card: Card }) {
    const [assignee, setAssignee] = useState(
        card.assignee ? String(card.assignee.id) : UNASSIGNED,
    );

    return (
        <>
            <DialogTitle>Card details</DialogTitle>
            <DialogDescription>In list {listTitle}</DialogDescription>

            <Form
                {...CardController.update.form({
                    board: board.id,
                    card: card.id,
                })}
                options={{ preserveScroll: true }}
                onSuccess={onClose}
                className="space-y-5"
            >
                {({ processing, errors }) => (
                    <>
                        <fieldset disabled={!canEdit} className="space-y-5">
                            <div className="grid gap-2">
                                <Label htmlFor="card-title">Title</Label>
                                <Input
                                    id="card-title"
                                    name="title"
                                    defaultValue={card.title}
                                    required
                                    maxLength={200}
                                />
                                <InputError message={errors.title} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="card-description">
                                    Description
                                </Label>
                                <Textarea
                                    id="card-description"
                                    name="description"
                                    defaultValue={card.description ?? ''}
                                    maxLength={10000}
                                    rows={5}
                                    placeholder="Add more detail…"
                                    className="max-h-72"
                                />
                                <InputError message={errors.description} />
                            </div>

                            <div className="grid gap-5 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="card-assignee">
                                        Assignee
                                    </Label>
                                    <input
                                        type="hidden"
                                        name="assignee_id"
                                        value={
                                            assignee === UNASSIGNED
                                                ? ''
                                                : assignee
                                        }
                                    />
                                    <Select
                                        value={assignee}
                                        onValueChange={setAssignee}
                                    >
                                        <SelectTrigger
                                            id="card-assignee"
                                            className="w-full"
                                        >
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value={UNASSIGNED}>
                                                Unassigned
                                            </SelectItem>
                                            {members.map((member) => (
                                                <SelectItem
                                                    key={member.user.id}
                                                    value={String(
                                                        member.user.id,
                                                    )}
                                                >
                                                    {member.user.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.assignee_id} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="card-due-date">
                                        Due date
                                    </Label>
                                    <Input
                                        id="card-due-date"
                                        name="due_date"
                                        type="date"
                                        defaultValue={card.due_date ?? ''}
                                    />
                                    <InputError message={errors.due_date} />
                                </div>
                            </div>
                        </fieldset>

                        <DialogFooter className="gap-2 sm:justify-between">
                            {canEdit ? (
                                <ConfirmDialog
                                    trigger={
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            className="text-destructive hover:text-destructive"
                                            data-test="delete-card-button"
                                        >
                                            <Trash2 aria-hidden />
                                            Delete card
                                        </Button>
                                    }
                                    title="Delete this card?"
                                    description={`“${card.title}” will be deleted. This cannot be undone.`}
                                    confirmLabel="Delete card"
                                    onConfirm={() =>
                                        router.delete(
                                            CardController.destroy({
                                                board: board.id,
                                                card: card.id,
                                            }),
                                            { preserveScroll: true },
                                        )
                                    }
                                />
                            ) : (
                                <span />
                            )}
                            <div className="flex flex-col-reverse gap-2 sm:flex-row">
                                <DialogClose asChild>
                                    <Button type="button" variant="secondary">
                                        {canEdit ? 'Cancel' : 'Close'}
                                    </Button>
                                </DialogClose>
                                {canEdit && (
                                    <Button
                                        type="submit"
                                        disabled={processing}
                                        data-test="save-card-button"
                                    >
                                        {processing && <Spinner />}
                                        Save
                                    </Button>
                                )}
                            </div>
                        </DialogFooter>
                    </>
                )}
            </Form>
        </>
    );
}
