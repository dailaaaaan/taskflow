# 0008. Drag & drop with dnd-kit and optimistic Inertia visits

- **Status:** Accepted
- **Date:** 2026-10-29

## Context

Cards move within and across lists, and lists move within a board. A move must feel instant, survive a failed request, and be operable with the keyboard and announced to screen readers. The server stays the source of truth for positions ([ADR-0003](0003-integer-gap-positions.md)), and phase 7 will apply moves made by other users to the same board state.

## Options considered

**Drag & drop library**

1. **`@dnd-kit/core` + `@dnd-kit/sortable` (6.x / 10.x).** Stable, MIT, the most used React option, compatible with React 19. Mouse, touch and keyboard sensors, plus live-region announcements for screen readers, are built in.
2. **`@dnd-kit/react` (0.x).** The library's next generation, but still pre-1.0 with beta releases every few days, so its API is still changing.
3. **`@atlaskit/pragmatic-drag-and-drop`.** Fast and maintained, but keyboard support is left to the application.

**Saving a move**

1. **JSON endpoint + local React state.** The client keeps a copy of the lists, applies the move, calls the API and rolls back by hand on failure. Two sources of truth to keep in sync with the Inertia props.
2. **Optimistic Inertia visit.** `router.optimistic()` applies the reducer to the `lists` prop at once. The endpoint redirects back, the client reloads `only: ['lists']`, and Inertia restores the previous props by itself when the request fails.

## Decision

dnd-kit 6 with the sortable preset, and optimistic Inertia visits.

- The page props are the only board state. `boardReducer` is a pure function used for the optimistic update now, and for remote events in phase 7.
- Moves send the target list and the index counted **without** the moved item. The client reducer and `App\Support\Position::insert` use the same convention.
- The `lists` and `members` props are closures, so the partial reload after a move only queries the lists.
- Pointer drags start anywhere on a card or list title (5 px threshold, so a click still opens the card; 200 ms press on touch, so a swipe still scrolls). Keyboard drags start only from a labelled handle, so Enter on a card still opens it.
- A custom keyboard coordinate getter moves ← → between lists and ↑ ↓ between cards of the same list. The sortable default considers lists and cards at once and can jump to a card of the same column.
- Failures (validation, 403/404, network) show a toast instead of Inertia's error modal.

## Consequences

- Positive: one source of truth, rollback for free, a single write per move in the common case, and keyboard and screen reader support verified by browser tests.
- Negative: dnd-kit 6 is in maintenance mode while its authors work on `@dnd-kit/react`. Moving later is contained, because only `sortable-board.tsx`, `list-column.tsx` and `sortable-card.tsx` import it.
- Follow-ups: in phase 7, remote events go through the same reducer with `router.replaceProp('lists', …)`.
