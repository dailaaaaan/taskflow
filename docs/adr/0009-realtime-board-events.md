# 0009. Keep boards in sync with reducer actions over a presence channel

- **Status:** Accepted
- **Date:** 2026-10-30

## Context

Members of a team work on the same board at once, so each one has to see the others' changes without reloading, and who is on the board. [ADR-0002](0002-pusher-for-realtime.md) chose Pusher Channels. This decision covers how a change travels from the server to the other browsers, and how that is tested without a real Pusher account.

## Options considered

**What an event carries**

1. **A "something changed" signal.** Each client reloads the lists. Simple, but every change costs one request per viewer and rebuilds the whole board.
2. **The change itself, as a `BoardAction`.** The client applies it with the reducer it already uses for its own moves ([ADR-0008](0008-drag-and-drop-with-optimistic-inertia-visits.md)). One code path, unit-tested, no extra requests.

**Local server for the end-to-end test**

1. **The real Pusher service.** Needs secrets in GitHub and external network, so tests would no longer be deterministic.
2. **Soketi.** AGPL-3.0 and without a release since 2024, which breaks the dependency rules of `CLAUDE.md`.
3. **Laravel Reverb as a development dependency.** First-party, MIT, and it speaks the Pusher protocol, so the real driver and `pusher-js` run unchanged.

## Decision

Option 2 for events, and Reverb for the end-to-end test.

- One abstract `BoardEvent` per board, sent on `presence-board.{id}` as `board.changed`, with a payload that is a `BoardAction`. Remote actions tolerate items that already exist or no longer do, because a reload may have included them first.
- All writes to lists and cards go through Actions, which dispatch the event with `->toOthers()` after the transaction. The browser sends `X-Socket-ID` through `http.onRequest`, because Inertia 3 has its own HTTP client, which Echo does not patch.
- `ShouldBroadcastNow`, since free hosting has no queue worker, plus `ShouldRescue`: the change is already saved, so a Pusher outage is reported instead of failing the request.
- Pusher rejects events over 10 KB and only a long card description can reach that. Such events become `board/stale`, and those clients reload the lists.
- The browser receives its connection settings as a page prop (`App\Support\RealtimeConfig`), not as `VITE_*` variables. One build works in every environment, and a `null` prop turns live updates off, so the app runs without a Pusher account.

## Consequences

- Positive: the reducer is the only place that changes the lists in the browser. No external service is needed to develop or test. A failed broadcast never costs the user their change.
- Negative: broadcasting synchronously adds a little latency to each write.
- Known limits, all corrected by the next page load:
    - Someone removed from a team keeps receiving events until they leave the board, because channels are only authorized when joining.
    - Changes made while a client is disconnected are not replayed after it reconnects.
    - Removing a member unassigns their cards in bulk without an event.
    - A remote change that arrives while the client's own move is failing can be lost in the rollback.
- Follow-ups: `laravel/reverb` is pinned to a commit of `dev-main`, because the latest release does not accept the `guzzlehttp/psr7` 3 required by Guzzle 8. Move it to a stable version when one is released. Reloading the lists after a reconnection fits phase 8.
