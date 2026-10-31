# Architecture Decision Records

An ADR captures one significant technical decision: the context, the options considered, the decision itself and its consequences. ADRs are immutable once accepted. A later decision **supersedes** an earlier one rather than editing it.

| #                                                            | Decision                                                                  | Status   |
| ------------------------------------------------------------ | ------------------------------------------------------------------------- | -------- |
| [0001](0001-laravel-inertia-monolith.md)                     | Laravel monolith with Inertia and React instead of a separate API and SPA | Accepted |
| [0002](0002-pusher-for-realtime.md)                          | Pusher Channels for real-time updates                                     | Accepted |
| [0003](0003-integer-gap-positions.md)                        | Integer positions with gaps for card and list ordering                    | Accepted |
| [0004](0004-actions-and-policies.md)                         | Actions for business logic and Policies for authorization                 | Accepted |
| [0005](0005-github-login-account-linking.md)                 | Link GitHub logins by verified email and never bypass 2FA                 | Accepted |
| [0006](0006-signed-invite-links.md)                          | Invite members with signed, reusable invite links                         | Accepted |
| [0007](0007-authorize-lists-and-cards-through-the-board.md)  | Authorize lists and cards through their board, with nested scoped routes  | Accepted |
| [0008](0008-drag-and-drop-with-optimistic-inertia-visits.md) | Drag & drop with dnd-kit and optimistic Inertia visits                    | Accepted |
| [0009](0009-realtime-board-events.md)                        | Keep boards in sync with reducer actions over a presence channel          | Accepted |
| [0010](0010-error-pages-and-failure-toasts.md)               | Error pages for visits, toasts for writes                                 | Accepted |
| [0011](0011-free-production-hosting.md)                      | Free production hosting and a public demo                                 | Accepted |

New ADRs start from [`template.md`](template.md).
