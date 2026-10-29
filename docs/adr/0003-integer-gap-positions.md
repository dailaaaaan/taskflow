# 0003. Order cards and lists with integer positions with gaps

- **Status:** Accepted
- **Date:** 2026-10-29

## Context

Users reorder cards within and across lists, and reorder lists within a board, concurrently and in real time. Each move should touch as few rows as possible, so that concurrent edits rarely conflict and the broadcast payload stays small.

## Options considered

1. **Contiguous integers (0, 1, 2, …).** Simple, but every move shifts all the following rows (O(n) writes).
2. **Fractional indexing with string keys.** Writes a single row per move, but ordering depends on byte-wise collation (`COLLATE "C"` in PostgreSQL), which is easy to get wrong across databases.
3. **Integer positions with gaps (1024, 2048, 3072, …).** A move takes the midpoint between its neighbours and writes a single row. When neighbours become adjacent, the affected list is renumbered in a single transaction.

## Decision

Option 3, implemented in `App\Support\Position` and fully unit-tested. Ordering is `(position, id)` so that ties are deterministic.

## Consequences

- Positive: one write per move in the common case, plain integer comparison, and identical behaviour on SQLite and PostgreSQL.
- Negative: an occasional rebalance writes the whole list. Lists are small (tens of cards), so this is cheap.
