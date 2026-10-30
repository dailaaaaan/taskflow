import type { Member } from './teams';

export type BoardColor =
    | 'indigo'
    | 'emerald'
    | 'amber'
    | 'rose'
    | 'sky'
    | 'violet';

export type Board = {
    id: number;
    title: string;
    color: BoardColor;
    /** Present when boards are listed on the team page. */
    lists_count?: number;
    cards_count?: number;
};

export type Card = {
    id: number;
    title: string;
    description: string | null;
    position: number;
    /** Calendar date, `YYYY-MM-DD`. */
    due_date: string | null;
    assignee: Pick<Member['user'], 'id' | 'name' | 'avatar'> | null;
};

export type BoardList = {
    id: number;
    title: string;
    position: number;
    cards: Card[];
};

/** Someone on the board right now, as sent by routes/channels.php. */
export type Viewer = Pick<Member['user'], 'id' | 'name' | 'avatar'>;

/** Public settings for Laravel Echo, from App\Support\RealtimeConfig. */
export type RealtimeConfig = {
    key: string;
    cluster: string | null;
    /** Only set for a Pusher-compatible server; Pusher itself uses the cluster. */
    host: string | null;
    port: number;
    scheme: string;
};

/** Mirrors App\Policies\BoardPolicy for the current user. UI hints only. */
export type BoardAbilities = {
    update: boolean;
    delete: boolean;
    collaborate: boolean;
};
