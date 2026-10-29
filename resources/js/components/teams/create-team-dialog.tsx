import { Form } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import TeamController from '@/actions/App/Http/Controllers/Teams/TeamController';
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

export default function CreateTeamDialog() {
    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button data-test="create-team-button">
                    <Plus aria-hidden />
                    New team
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>Create a team</DialogTitle>
                <DialogDescription>
                    Teams share boards. You will be the team&apos;s first admin
                    and can invite others afterwards.
                </DialogDescription>

                <Form
                    {...TeamController.store.form()}
                    disableWhileProcessing
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="team-name">Team name</Label>
                                <Input
                                    id="team-name"
                                    name="name"
                                    required
                                    maxLength={100}
                                    autoFocus
                                    placeholder="e.g. Product Team"
                                />
                                <InputError message={errors.name} />
                            </div>

                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button type="button" variant="secondary">
                                        Cancel
                                    </Button>
                                </DialogClose>
                                <Button
                                    type="submit"
                                    data-test="submit-team-button"
                                >
                                    {processing && <Spinner />}
                                    Create team
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
