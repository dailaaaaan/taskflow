import { Form, Head, Link } from '@inertiajs/react';
import { Users } from 'lucide-react';
import AppLogoIcon from '@/components/app-logo-icon';
import { roleLabels } from '@/components/teams/role-badge';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { dashboard } from '@/routes';
import type { InvitationPreview } from '@/types';

type Props = {
    invitation: InvitationPreview;
    /** Signed URL of the accept endpoint; the signature authorizes the POST. */
    acceptUrl: string;
};

export default function InvitationShow({ invitation, acceptUrl }: Props) {
    return (
        <>
            <Head title={`Join ${invitation.team}`} />

            <div className="flex flex-1 items-center justify-center p-4 md:p-6">
                <div className="w-full max-w-md space-y-6 rounded-xl border bg-card p-8 text-center shadow-xs">
                    <div className="mx-auto flex size-12 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                        <AppLogoIcon className="size-7 fill-current" />
                    </div>

                    <div className="space-y-2">
                        <h1 className="text-xl font-semibold tracking-tight">
                            Join {invitation.team}
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            {invitation.invited_by
                                ? `${invitation.invited_by} invited you to join as`
                                : 'You have been invited to join as'}{' '}
                            <strong className="text-foreground">
                                {roleLabels[invitation.role].toLowerCase()}
                            </strong>
                            .
                        </p>
                        <p className="inline-flex items-center gap-1.5 text-sm text-muted-foreground">
                            <Users className="size-4" aria-hidden />
                            {invitation.members_count}{' '}
                            {invitation.members_count === 1
                                ? 'member'
                                : 'members'}
                        </p>
                    </div>

                    <Form
                        action={acceptUrl}
                        method="post"
                        className="flex flex-col gap-2"
                    >
                        {({ processing }) => (
                            <>
                                <Button
                                    type="submit"
                                    disabled={processing}
                                    data-test="accept-invitation-button"
                                >
                                    {processing && <Spinner />}
                                    Join team
                                </Button>
                                <Button variant="ghost" asChild>
                                    <Link href={dashboard()}>Not now</Link>
                                </Button>
                            </>
                        )}
                    </Form>
                </div>
            </div>
        </>
    );
}
