import { Form } from '@inertiajs/react';
import { Plus, X } from 'lucide-react';
import ListController from '@/actions/App/Http/Controllers/Boards/ListController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useInlineForm } from '@/hooks/use-inline-form';
import type { Board } from '@/types';

/**
 * "Add a list" at the end of the board. Stays open after each list so
 * several can be added in a row; Escape closes it.
 */
export default function AddListForm({ board }: { board: Board }) {
    const form = useInlineForm();

    if (!form.isOpen) {
        return (
            <Button
                ref={form.openButton}
                variant="ghost"
                className="w-72 shrink-0 justify-start bg-muted/50"
                onClick={form.open}
                data-test="add-list-button"
            >
                <Plus aria-hidden />
                Add a list
            </Button>
        );
    }

    return (
        <Form
            {...ListController.store.form(board.id)}
            options={{ preserveScroll: true }}
            resetOnSuccess
            className="w-72 shrink-0 space-y-2 rounded-xl bg-muted/60 p-2"
        >
            {({ processing, errors }) => (
                <>
                    <Input
                        name="title"
                        aria-label="List title"
                        placeholder="Enter list title…"
                        required
                        maxLength={100}
                        autoFocus
                        data-test="new-list-title"
                        onKeyDown={(event) => {
                            if (event.key === 'Escape') {
                                form.close();
                            }
                        }}
                    />
                    <InputError message={errors.title} />
                    <div className="flex items-center gap-1">
                        <Button
                            type="submit"
                            size="sm"
                            disabled={processing}
                            data-test="submit-list-button"
                        >
                            Add list
                        </Button>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            className="size-8"
                            aria-label="Stop adding lists"
                            onClick={() => form.close()}
                        >
                            <X />
                        </Button>
                    </div>
                </>
            )}
        </Form>
    );
}
