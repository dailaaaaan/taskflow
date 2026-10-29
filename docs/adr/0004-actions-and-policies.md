# 0004. Use Actions for business logic and Policies for authorization

- **Status:** Accepted
- **Date:** 2026-10-29

## Context

In MVC applications, controllers tend to grow into "fat controllers" that mix HTTP handling, validation, authorization, persistence and side effects. That code is hard to test and hard to reuse, for example from a future API or console command.

## Options considered

1. **Logic in controllers.** Quick to start with, but untestable in isolation and duplicated across entry points.
2. **Service classes per aggregate (`CardService`).** Better, but they tend to become large, multi-purpose classes.
3. **Single-purpose Action classes plus Laravel Policies.** One class per use case (`MoveCard`, `InviteMember`) with a single `handle()` method. Authorization lives in Policies, validation in Form Requests.

## Decision

Option 3. Controllers only coordinate: Form Request → `Gate::authorize()` → Action → response. Actions are `final`, have no HTTP dependencies and wrap multi-row writes in transactions. Architecture tests enforce these rules.

## Consequences

- Positive: each use case is testable without HTTP. Permissions are declared in one place and tested as a matrix. The same Actions can serve a future API.
- Negative: more files, plus a convention that has to be learned.
