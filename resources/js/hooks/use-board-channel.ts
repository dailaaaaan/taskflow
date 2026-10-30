import { http, router } from '@inertiajs/react';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import { useEffect, useState } from 'react';
import { boardReducer } from '@/lib/board-reducer';
import type { BoardEvent } from '@/lib/board-reducer';
import type { BoardList, RealtimeConfig, Viewer } from '@/types';

function connect(config: RealtimeConfig): Echo<'pusher'> {
    return new Echo({
        broadcaster: 'pusher',
        Pusher,
        key: config.key,
        cluster: config.cluster ?? '',
        forceTLS: config.scheme === 'https',
        ...(config.host && {
            wsHost: config.host,
            wsPort: config.port,
            wssPort: config.port,
            enabledTransports: ['ws', 'wss'],
        }),
    });
}

function applyRemoteEvent(event: BoardEvent): void {
    if (event.type === 'board/stale') {
        router.reload({ only: ['lists'] });

        return;
    }

    router.replaceProp('lists', (_: unknown, props: { lists: BoardList[] }) =>
        boardReducer(props.lists, event),
    );
}

/**
 * Keeps the lists prop in sync with the other viewers of the board while the
 * page is open, and returns who is on the board right now. Does nothing when
 * live updates are off.
 */
export function useBoardChannel(
    boardId: number,
    realtime: RealtimeConfig | null,
): Viewer[] {
    const [viewers, setViewers] = useState<Viewer[]>([]);
    // Every full Inertia visit sends an equal but new object, and reconnecting
    // on each one would make the viewer avatars flicker for everybody.
    const [config] = useState(realtime);

    useEffect(() => {
        if (!config) {
            return;
        }

        const echo = connect(config);
        const channel = `board.${boardId}`;

        // Tells the server which socket sent a request, so toOthers() skips it.
        const stopSendingSocketId = http.onRequest((request) => {
            const socketId = echo.socketId();

            return socketId
                ? {
                      ...request,
                      headers: { ...request.headers, 'X-Socket-ID': socketId },
                  }
                : request;
        });

        echo.join(channel)
            .here((users: Viewer[]) => setViewers(users))
            .joining((user: Viewer) =>
                setViewers((current) => [
                    ...current.filter(({ id }) => id !== user.id),
                    user,
                ]),
            )
            .leaving((user: Viewer) =>
                setViewers((current) =>
                    current.filter(({ id }) => id !== user.id),
                ),
            )
            .listen('.board.changed', applyRemoteEvent);

        return () => {
            stopSendingSocketId();
            echo.leave(channel);
            echo.disconnect();
        };
    }, [boardId, config]);

    return viewers;
}
