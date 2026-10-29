export type TeamRole = 'admin' | 'member';

export type Team = {
    id: number;
    name: string;
    /** Present when the team is listed for the current user. */
    role?: TeamRole;
    members_count?: number;
    boards_count?: number;
};

export type Member = {
    /** Membership id, used by the member management routes. */
    id: number;
    role: TeamRole;
    joined_at: string | null;
    is_current_user: boolean;
    user: {
        id: number;
        name: string;
        email: string;
        avatar: string | null;
    };
};

export type Invitation = {
    id: number;
    role: TeamRole;
    expires_at: string;
    created_by: string | null;
    url: string;
};

export type InvitationPreview = {
    team: string;
    role: TeamRole;
    invited_by: string | null;
    members_count: number;
    expires_at: string;
};

/** Mirrors App\Policies\TeamPolicy for the current user. UI hints only. */
export type TeamAbilities = {
    update: boolean;
    delete: boolean;
    invite: boolean;
    manageMembers: boolean;
    leave: boolean;
};
