# 0007. Authorize lists and cards through their board

- **Status:** Accepted
- **Date:** 2026-10-29

## Context

Lists and cards only exist inside a board, and a board belongs to a team. Every request that touches a list or a card has to answer two questions: is the user a member of the board's team, and does this list or card really belong to that board? Getting the second one wrong lets a user edit another team's card by changing an id in the URL (an insecure direct object reference). The first plan was one policy per model (`BoardPolicy`, `CardPolicy`).

## Options considered

1. **Flat routes with one policy per model** (`/cards/{card}` authorized by `CardPolicy`). Every policy walks card → list → board → team, which adds queries, and the same membership rule is repeated in three policies.
2. **Routes nested under the board** (`/boards/{board}/cards/{card}`). `BoardPolicy` authorizes the board, and `Route::scopeBindings()` resolves the list or card only through that board (`$board->lists()`, `$board->cards()`), so an id from another board is a `404` before any code runs.

## Decision

Option 2. `BoardPolicy` gains a `collaborate` ability for work on lists and cards. Today it equals `view`, since every member collaborates, but it is a separate ability so that a future read-only role only changes one method. `Board::cards()` is a `HasManyThrough` relation that scopes the `{card}` binding.

## Consequences

- Positive: one authorization question per request, answered in one place. The realtime presence channel (`board.{id}`) is authorized by the same policy. Cross-board ids are covered by `404` tests.
- Negative: longer URLs, and the client always sends the board id. Moving a card to another board would need its own endpoint (it is not a feature).
- Follow-ups: the move endpoint of phase 6 validates that the target list belongs to the same board.
