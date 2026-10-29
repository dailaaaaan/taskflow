import { Form } from '@inertiajs/react';
import { Check } from 'lucide-react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import {
    boardColorClasses,
    boardColorLabels,
    boardColors,
} from '@/components/boards/board-colors';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';
import type { Board } from '@/types';
import type { RouteFormDefinition } from '@/wayfinder';

type Props = {
    trigger: ReactNode;
    title: string;
    description: string;
    /** Wayfinder form definition: create (store) or edit (update). */
    form: RouteFormDefinition<'post'>;
    submitLabel: string;
    /** Current values when editing an existing board. */
    board?: Board;
};

/**
 * Title and colour form shared by "New board" and "Edit board".
 */
export default function BoardFormDialog({
    trigger,
    title,
    description,
    form,
    submitLabel,
    board,
}: Props) {
    const [open, setOpen] = useState(false);
    const currentColor = board?.color ?? 'indigo';

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent>
                <DialogTitle>{title}</DialogTitle>
                <DialogDescription>{description}</DialogDescription>

                <Form
                    {...form}
                    options={{ preserveScroll: true }}
                    onSuccess={() => setOpen(false)}
                    disableWhileProcessing
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="board-title">Title</Label>
                                <Input
                                    id="board-title"
                                    name="title"
                                    defaultValue={board?.title}
                                    required
                                    maxLength={100}
                                    autoFocus
                                    placeholder="e.g. Product roadmap"
                                />
                                <InputError message={errors.title} />
                            </div>

                            <fieldset className="space-y-2">
                                <legend className="text-sm leading-none font-medium">
                                    Color
                                </legend>
                                <div className="flex flex-wrap gap-3 pt-1">
                                    {boardColors.map((color) => (
                                        <label
                                            key={color}
                                            className={cn(
                                                'flex size-8 cursor-pointer items-center justify-center rounded-full text-white ring-offset-2 ring-offset-background has-checked:ring-2 has-checked:ring-foreground has-focus-visible:ring-2 has-focus-visible:ring-ring',
                                                boardColorClasses[color],
                                            )}
                                        >
                                            <input
                                                type="radio"
                                                name="color"
                                                value={color}
                                                defaultChecked={
                                                    color === currentColor
                                                }
                                                className="peer sr-only"
                                            />
                                            <Check
                                                className="hidden size-4 peer-checked:block"
                                                aria-hidden
                                            />
                                            <span className="sr-only">
                                                {boardColorLabels[color]}
                                            </span>
                                        </label>
                                    ))}
                                </div>
                                <InputError message={errors.color} />
                            </fieldset>

                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button type="button" variant="secondary">
                                        Cancel
                                    </Button>
                                </DialogClose>
                                <Button
                                    type="submit"
                                    data-test="submit-board-button"
                                >
                                    {processing && <Spinner />}
                                    {submitLabel}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
