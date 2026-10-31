import { Head, Link } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { Button } from '@/components/ui/button';
import { dashboard, home } from '@/routes';

const errors = {
    403: {
        title: "You don't have access to this page",
        description:
            'If it belongs to a team, ask one of its admins for an invite link.',
    },
    404: {
        title: 'Page not found',
        description:
            'It may have been deleted or moved, or the link may be wrong.',
    },
    429: {
        title: 'Too many requests',
        description: 'Please wait a minute before trying again.',
    },
    500: {
        title: 'Something went wrong on our side',
        description: 'The error has been logged. Please try again in a moment.',
    },
    503: {
        title: 'TaskFlow is temporarily unavailable',
        description: 'Please try again in a few minutes.',
    },
};

type Props = {
    status: keyof typeof errors;
    signedIn: boolean;
};

export default function ErrorPage({ status, signedIn }: Props) {
    const { title, description } = errors[status];

    return (
        <>
            <Head title={title} />

            <main className="flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 text-center">
                <AppLogoIcon className="size-9 fill-current" aria-hidden />
                <div className="space-y-2">
                    <p className="text-sm font-medium text-muted-foreground tabular-nums">
                        Error {status}
                    </p>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        {title}
                    </h1>
                    <p className="max-w-sm text-sm text-muted-foreground">
                        {description}
                    </p>
                </div>
                <Button asChild>
                    <Link
                        href={signedIn ? dashboard() : home()}
                        data-test="error-page-link"
                    >
                        {signedIn ? 'Back to your teams' : 'Back to TaskFlow'}
                    </Link>
                </Button>
            </main>
        </>
    );
}
