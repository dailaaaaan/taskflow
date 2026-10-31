# 0010. Error pages for visits, toasts for writes

- **Status:** Accepted
- **Date:** 2026-10-31

## Context

Inertia shows any non-Inertia error response in a modal with the raw HTML, which is not a finished product. The app needs its own error pages. But on a shared board many failed writes are expected, not exceptional: two people delete the same list, or a member is removed while the page is open. The Inertia client treats an Inertia response with an error status as a page, so rendering the error page for every request would replace a whole board with a `404` because of a clash over one card.

## Options considered

1. **Error page for every request.** One rule, but a clash over a single card throws away the board the user is working on.
2. **Error page for `GET` visits, toast for writes.** A page that cannot be shown is replaced by one that explains why. A write that fails leaves the user where they are.
3. **Handle failures in each form.** Precise messages, but every form needs the same code and a new form that forgets it falls back to the modal.

## Decision

- `Inertia::handleExceptionsUsing()` in `bootstrap/app.php` renders `errors/show` for `GET` visits that end in `403`, `404`, `429`, `500` or `503`. Writes and JSON clients keep the plain response.
- Server errors keep Laravel's debug screen while `APP_DEBUG` is on.
- The page gets no shared data. Sharing would resolve the user again, which queries the database, and the database may be the reason for the `500`. `Auth::hasUser()` tells it whether someone is signed in without a query.
- A global listener (`resources/js/lib/visit-errors.ts`) turns every other failed request into a toast, and network failures too. The Inertia client runs it after each visit's own `onHttpException`, so drag & drop keeps its specific message and its rollback.

## Consequences

- Positive: a new form or endpoint gets the right behaviour without any code. A clash between two users on a board costs them a toast, not their page.
- Negative: toasts are generic. They can say what went wrong, but not which card or list.
- Skeletons follow from the same idea of keeping the page stable: they appear inside the layout only when a navigation takes longer than 250 ms. Props are not deferred to show one earlier, because that would add a second request to load the main content of the page.
