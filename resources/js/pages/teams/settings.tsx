import { Form, Head, router } from '@inertiajs/react';
import { Check, Copy, Link2, LogOut, Trash2, UserMinus } from 'lucide-react';
import { useState } from 'react';
import InvitationController from '@/actions/App/Http/Controllers/Teams/InvitationController';
import TeamController from '@/actions/App/Http/Controllers/Teams/TeamController';
import ConfirmDialog from '@/components/confirm-dialog';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import MemberAvatar from '@/components/teams/member-avatar';
import RoleBadge, { roleLabels } from '@/components/teams/role-badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useClipboard } from '@/hooks/use-clipboard';
import { dashboard } from '@/routes';
import { destroy, edit, leave, show } from '@/routes/teams';
import { destroy as revokeInvitation } from '@/routes/teams/invitations';
import {
    destroy as removeMember,
    update as updateMember,
} from '@/routes/teams/members';
import type {
    Invitation,
    Member,
    Team,
    TeamAbilities,
    TeamRole,
} from '@/types';

type Props = {
    team: Team;
    members: Member[];
    invitations: Invitation[];
    can: TeamAbilities;
};

const roles: TeamRole[] = ['member', 'admin'];

function daysUntil(date: string): number {
    return Math.max(
        0,
        Math.ceil((new Date(date).getTime() - Date.now()) / 86_400_000),
    );
}

export default function TeamSettings({
    team,
    members,
    invitations,
    can,
}: Props) {
    return (
        <>
            <Head title={`${team.name} settings`} />

            <div className="mx-auto flex w-full max-w-3xl flex-1 flex-col gap-10 p-4 md:p-6">
                <header className="space-y-1">
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Team settings
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Manage {team.name}, its members and its invite links.
                    </p>
                </header>

                <GeneralSection team={team} canUpdate={can.update} />
                <Separator />
                <MembersSection
                    team={team}
                    members={members}
                    canManage={can.manageMembers}
                />

                {can.invite && (
                    <>
                        <Separator />
                        <InvitationsSection
                            team={team}
                            invitations={invitations}
                        />
                    </>
                )}

                <Separator />
                <LeaveSection team={team} />

                {can.delete && (
                    <>
                        <Separator />
                        <DeleteSection team={team} />
                    </>
                )}
            </div>
        </>
    );
}

function GeneralSection({
    team,
    canUpdate,
}: {
    team: Team;
    canUpdate: boolean;
}) {
    return (
        <section className="space-y-6">
            <Heading
                variant="small"
                title="General"
                description="The team's name, visible to all its members."
            />

            {canUpdate ? (
                <Form
                    {...TeamController.update.form(team.id)}
                    options={{ preserveScroll: true }}
                    className="flex flex-col gap-3 sm:flex-row sm:items-start"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid flex-1 gap-2">
                                <Label htmlFor="name" className="sr-only">
                                    Team name
                                </Label>
                                <Input
                                    id="name"
                                    name="name"
                                    defaultValue={team.name}
                                    required
                                    maxLength={100}
                                />
                                <InputError message={errors.name} />
                            </div>
                            <Button type="submit" disabled={processing}>
                                Save
                            </Button>
                        </>
                    )}
                </Form>
            ) : (
                <p className="text-sm">{team.name}</p>
            )}
        </section>
    );
}

function MembersSection({
    team,
    members,
    canManage,
}: {
    team: Team;
    members: Member[];
    canManage: boolean;
}) {
    const changeRole = (member: Member, role: string) =>
        router.patch(
            updateMember({ team: team.id, membership: member.id }),
            { role },
            { preserveScroll: true },
        );

    const remove = (member: Member) =>
        router.delete(removeMember({ team: team.id, membership: member.id }), {
            preserveScroll: true,
        });

    return (
        <section className="space-y-6">
            <Heading
                variant="small"
                title="Members"
                description="Admins manage the team, its members and its boards. Members collaborate on boards."
            />

            <ul className="divide-y rounded-lg border" data-test="members-list">
                {members.map((member) => (
                    <li
                        key={member.id}
                        className="flex flex-wrap items-center gap-3 p-3"
                    >
                        <MemberAvatar user={member.user} />
                        <div className="grid min-w-0 flex-1 text-sm leading-tight">
                            <span className="truncate font-medium">
                                {member.user.name}
                                {member.is_current_user && (
                                    <span className="text-muted-foreground">
                                        {' '}
                                        (you)
                                    </span>
                                )}
                            </span>
                            <span className="truncate text-xs text-muted-foreground">
                                {member.user.email}
                            </span>
                        </div>

                        {canManage ? (
                            <div className="flex items-center gap-2">
                                <Select
                                    value={member.role}
                                    onValueChange={(role) =>
                                        changeRole(member, role)
                                    }
                                >
                                    <SelectTrigger
                                        className="w-28"
                                        aria-label={`Role of ${member.user.name}`}
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {roles.map((role) => (
                                            <SelectItem key={role} value={role}>
                                                {roleLabels[role]}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>

                                {!member.is_current_user && (
                                    <ConfirmDialog
                                        trigger={
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                aria-label={`Remove ${member.user.name}`}
                                            >
                                                <UserMinus />
                                            </Button>
                                        }
                                        title={`Remove ${member.user.name}?`}
                                        description={`They will lose access to ${team.name} and its boards.`}
                                        confirmLabel="Remove member"
                                        onConfirm={() => remove(member)}
                                    />
                                )}
                            </div>
                        ) : (
                            <RoleBadge role={member.role} />
                        )}
                    </li>
                ))}
            </ul>
        </section>
    );
}

function InvitationsSection({
    team,
    invitations,
}: {
    team: Team;
    invitations: Invitation[];
}) {
    const [role, setRole] = useState<TeamRole>('member');

    return (
        <section className="space-y-6">
            <Heading
                variant="small"
                title="Invite links"
                description="Anyone with an active link can join this team with its role. Links expire after 7 days and can be revoked at any time."
            />

            <Form
                {...InvitationController.store.form(team.id)}
                options={{ preserveScroll: true }}
                className="flex flex-col gap-3 sm:flex-row sm:items-center"
            >
                {({ processing }) => (
                    <>
                        <input type="hidden" name="role" value={role} />
                        <Label htmlFor="invite-role" className="text-sm">
                            Join as
                        </Label>
                        <Select
                            value={role}
                            onValueChange={(value) =>
                                setRole(value as TeamRole)
                            }
                        >
                            <SelectTrigger id="invite-role" className="w-32">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {roles.map((option) => (
                                    <SelectItem key={option} value={option}>
                                        {roleLabels[option]}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <Button
                            type="submit"
                            disabled={processing}
                            data-test="create-invite-button"
                        >
                            <Link2 aria-hidden />
                            Create invite link
                        </Button>
                    </>
                )}
            </Form>

            {invitations.length > 0 ? (
                <ul
                    className="divide-y rounded-lg border"
                    data-test="invitations-list"
                >
                    {invitations.map((invitation) => (
                        <InvitationRow
                            key={invitation.id}
                            team={team}
                            invitation={invitation}
                        />
                    ))}
                </ul>
            ) : (
                <p className="text-sm text-muted-foreground">
                    No active invite links.
                </p>
            )}
        </section>
    );
}

function InvitationRow({
    team,
    invitation,
}: {
    team: Team;
    invitation: Invitation;
}) {
    const [copiedText, copy] = useClipboard();
    const copied = copiedText === invitation.url;
    const days = daysUntil(invitation.expires_at);

    return (
        <li className="flex flex-wrap items-center gap-3 p-3">
            <RoleBadge role={invitation.role} />
            <div className="grid min-w-0 flex-1 text-xs text-muted-foreground">
                <span
                    className="truncate font-mono text-foreground"
                    data-test="invitation-url"
                >
                    {invitation.url}
                </span>
                <span>
                    Expires in {days} {days === 1 ? 'day' : 'days'}
                    {invitation.created_by &&
                        ` · created by ${invitation.created_by}`}
                </span>
            </div>
            <Button
                variant="outline"
                size="sm"
                onClick={() => copy(invitation.url)}
            >
                {copied ? <Check aria-hidden /> : <Copy aria-hidden />}
                {copied ? 'Copied' : 'Copy'}
            </Button>
            <ConfirmDialog
                trigger={
                    <Button
                        variant="ghost"
                        size="icon"
                        aria-label="Revoke invite link"
                    >
                        <Trash2 />
                    </Button>
                }
                title="Revoke this invite link?"
                description="People who have not joined yet will no longer be able to use it."
                confirmLabel="Revoke link"
                onConfirm={() =>
                    router.delete(
                        revokeInvitation({
                            team: team.id,
                            invitation: invitation.id,
                        }),
                        { preserveScroll: true },
                    )
                }
            />
        </li>
    );
}

function LeaveSection({ team }: { team: Team }) {
    return (
        <section className="space-y-4">
            <Heading
                variant="small"
                title="Leave team"
                description="You will lose access to this team's boards. The last admin cannot leave: promote someone first."
            />
            <ConfirmDialog
                trigger={
                    <Button variant="outline" data-test="leave-team-button">
                        <LogOut aria-hidden />
                        Leave team
                    </Button>
                }
                title={`Leave ${team.name}?`}
                description="You will need a new invite link to join again."
                confirmLabel="Leave team"
                onConfirm={() => router.delete(leave(team.id))}
            />
        </section>
    );
}

function DeleteSection({ team }: { team: Team }) {
    return (
        <section className="space-y-4">
            <Heading
                variant="small"
                title="Delete team"
                description="Permanently delete this team with all of its boards, lists and cards."
            />
            <div className="rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10">
                <ConfirmDialog
                    trigger={
                        <Button
                            variant="destructive"
                            data-test="delete-team-button"
                        >
                            <Trash2 aria-hidden />
                            Delete team
                        </Button>
                    }
                    title={`Delete ${team.name}?`}
                    description="All of its boards, lists and cards will be deleted. This cannot be undone."
                    confirmLabel="Delete team"
                    onConfirm={() => router.delete(destroy(team.id))}
                />
            </div>
        </section>
    );
}

TeamSettings.layout = ({ team }: { team: Team }) => ({
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: team.name, href: show(team.id) },
        { title: 'Settings', href: edit(team.id) },
    ],
});
