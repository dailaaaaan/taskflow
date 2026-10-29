import type { ReactNode } from 'react';
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

type Props = {
    trigger: ReactNode;
    title: string;
    description: ReactNode;
    confirmLabel: string;
    onConfirm: () => void;
    destructive?: boolean;
};

/**
 * Asks for confirmation before an irreversible action (delete, remove, leave).
 */
export default function ConfirmDialog({
    trigger,
    title,
    description,
    confirmLabel,
    onConfirm,
    destructive = true,
}: Props) {
    return (
        <Dialog>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent>
                <DialogTitle>{title}</DialogTitle>
                <DialogDescription>{description}</DialogDescription>
                <DialogFooter className="gap-2">
                    <DialogClose asChild>
                        <Button variant="secondary">Cancel</Button>
                    </DialogClose>
                    <DialogClose asChild>
                        <Button
                            variant={destructive ? 'destructive' : 'default'}
                            onClick={onConfirm}
                            data-test="confirm-button"
                        >
                            {confirmLabel}
                        </Button>
                    </DialogClose>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
