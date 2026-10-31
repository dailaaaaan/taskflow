import { Head, Link, usePage } from '@inertiajs/react';
import {
    Eye,
    KeyRound,
    Link2,
    Move,
    RefreshCw,
    ShieldCheck,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import AppLogoIcon from '@/components/app-logo-icon';
import BoardPreview from '@/components/landing/board-preview';
import { Button } from '@/components/ui/button';
import { dashboard, login, register } from '@/routes';

const repositoryUrl = 'https://github.com/dailaaaaan/taskflow';

const features: { icon: LucideIcon; title: string; description: string }[] = [
    {
        icon: RefreshCw,
        title: 'Live on every screen',
        description:
            'New cards, edits and moves reach everyone on the board without a reload.',
    },
    {
        icon: Eye,
        title: 'See who is here',
        description:
            'Avatars next to the board title show who else has it open right now.',
    },
    {
        icon: Move,
        title: 'Mouse, touch or keyboard',
        description:
            'Drag cards and lists any way you like. Screen readers hear every step.',
    },
    {
        icon: ShieldCheck,
        title: 'Clear team roles',
        description:
            'Admins manage people and boards. Members work on lists and cards.',
    },
    {
        icon: Link2,
        title: 'Invite links',
        description:
            'Share a signed link with a teammate. It expires on its own.',
    },
    {
        icon: KeyRound,
        title: 'Serious about sign-in',
        description: 'Protect your account with two-factor codes or a passkey.',
    },
];

export default function Welcome() {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Real-time Kanban for teams">
                <meta
                    head-key="description"
                    name="description"
                    content="TaskFlow is a real-time Kanban board for teams: move a card and everyone on the board sees it instantly."
                />
            </Head>

            <div className="flex min-h-svh flex-col bg-background text-foreground">
                <header className="mx-auto flex w-full max-w-6xl items-center justify-between gap-4 px-6 py-5">
                    <span className="flex items-center gap-2 font-semibold">
                        <AppLogoIcon
                            className="size-6 fill-current"
                            aria-hidden
                        />
                        TaskFlow
                    </span>
                    <nav aria-label="Main" className="flex items-center gap-2">
                        {auth.user ? (
                            <Button asChild>
                                <Link href={dashboard()}>Dashboard</Link>
                            </Button>
                        ) : (
                            <>
                                <Button variant="ghost" asChild>
                                    <Link href={login()}>Log in</Link>
                                </Button>
                                <Button asChild>
                                    <Link href={register()}>Get started</Link>
                                </Button>
                            </>
                        )}
                    </nav>
                </header>

                <main className="flex-1">
                    <section className="mx-auto w-full max-w-6xl px-6 pt-12 pb-16 sm:pt-20">
                        <h1 className="max-w-3xl text-4xl font-semibold tracking-tight text-balance sm:text-6xl">
                            Move a card. Your team sees it instantly.
                        </h1>
                        <p className="mt-6 max-w-2xl text-lg text-pretty text-muted-foreground">
                            TaskFlow is a real-time Kanban board for teams. Plan
                            your work in lists and cards, push it forward
                            together, and always know who else is looking.
                        </p>
                        <div className="mt-8 flex flex-wrap gap-3">
                            <Button size="lg" asChild>
                                {auth.user ? (
                                    <Link href={dashboard()}>
                                        Go to your teams
                                    </Link>
                                ) : (
                                    <Link href={register()}>
                                        Create your first board
                                    </Link>
                                )}
                            </Button>
                            <Button size="lg" variant="outline" asChild>
                                <a href={repositoryUrl}>View the source</a>
                            </Button>
                        </div>

                        <div className="mt-14">
                            <BoardPreview />
                        </div>
                    </section>

                    <section
                        aria-labelledby="features-heading"
                        className="border-t bg-muted/40"
                    >
                        <div className="mx-auto w-full max-w-6xl px-6 py-16">
                            <h2
                                id="features-heading"
                                className="max-w-xl text-2xl font-semibold tracking-tight text-balance sm:text-3xl"
                            >
                                Everything your team needs on one board
                            </h2>
                            <ul className="mt-10 grid gap-x-8 gap-y-10 sm:grid-cols-2 lg:grid-cols-3">
                                {features.map(
                                    ({ icon: Icon, title, description }) => (
                                        <li key={title}>
                                            <Icon
                                                className="size-5"
                                                aria-hidden
                                            />
                                            <h3 className="mt-3 font-medium">
                                                {title}
                                            </h3>
                                            <p className="mt-1 text-sm text-pretty text-muted-foreground">
                                                {description}
                                            </p>
                                        </li>
                                    ),
                                )}
                            </ul>
                        </div>
                    </section>
                </main>

                <footer className="border-t">
                    <div className="mx-auto flex w-full max-w-6xl flex-wrap items-center justify-between gap-x-6 gap-y-2 px-6 py-6 text-sm text-muted-foreground">
                        <p>
                            Open source. Built with Laravel, React and
                            PostgreSQL.
                        </p>
                        <a
                            href={repositoryUrl}
                            className="underline underline-offset-4 hover:text-foreground"
                        >
                            GitHub repository
                        </a>
                    </div>
                </footer>
            </div>
        </>
    );
}
