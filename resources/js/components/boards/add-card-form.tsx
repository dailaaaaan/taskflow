import { Form } from '@inertiajs/react';
import { Plus, X } from 'lucide-react';
import { useState } from 'react';
import CardController from '@/actions/App/Http/Controllers/Boards/CardController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import type { Board, BoardList } from '@/types';

/**
 * "Add a card" at the bottom of a list. Enter adds the card and keeps the
 * form open for the next one; Shift+Enter inserts a line break.
 */
export default function AddCardForm({
    board,
    list,
}: {
    board: Board;
    list: BoardList;
}) {
    const [adding, setAdding] = useState(false);

    if (!adding) {
        return (
            <Button
                variant="ghost"
                size="sm"
                className="justify-start text-muted-foreground"
                onClick={() => setAdding(true)}
                data-test="add-card-button"
            >
                <Plus aria-hidden />
                Add a card
            </Button>
        );
    }

    return (
        <Form
            {...CardController.store.form({ board: board.id, list: list.id })}
            options={{ preserveScroll: true }}
            resetOnSuccess
            className="space-y-2"
        >
            {({ processing, errors }) => (
                <>
                    <Textarea
                        name="title"
                        aria-label={`Title of the new card in ${list.title}`}
                        placeholder="Enter a title for this card…"
                        required
                        maxLength={200}
                        autoFocus
                        className="min-h-14 bg-card"
                        data-test="new-card-title"
                        onKeyDown={(event) => {
                            if (event.key === 'Enter' && !event.shiftKey) {
                                event.preventDefault();
                                event.currentTarget.form?.requestSubmit();
                            }

                            if (event.key === 'Escape') {
                                setAdding(false);
                            }
                        }}
                    />
                    <InputError message={errors.title} />
                    <div className="flex items-center gap-1">
                        <Button
                            type="submit"
                            size="sm"
                            disabled={processing}
                            data-test="submit-card-button"
                        >
                            Add card
                        </Button>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            className="size-8"
                            aria-label="Stop adding cards"
                            onClick={() => setAdding(false)}
                        >
                            <X />
                        </Button>
                    </div>
                </>
            )}
        </Form>
    );
}
