# 0001. Build a Laravel monolith with Inertia and React

- **Status:** Accepted
- **Date:** 2026-10-29

## Context

TaskFlow needs a rich, interactive UI (drag & drop, optimistic updates, live changes) and a backend with authentication, relational data and fine-grained authorization. It is built by a single developer with a tight timeline, and it must run on free hosting.

## Options considered

1. **Classic MVC with Blade templates.** The simplest option, but complex client-side interactions like drag & drop and optimistic state become awkward.
2. **Separate REST API (Laravel + Sanctum) and React SPA.** This shows API design, but means two deployments, token or cross-site cookie authentication, CORS, duplicated validation and contract drift between client and server.
3. **Laravel monolith with Inertia rendering React pages.** Controllers return page components with typed props. Session authentication, one deployment, and full React on the client.

## Decision

Option 3. Inertia gives the interactivity of an SPA while keeping routing, authentication, validation and authorization in one place. Board interactions that must not reload page props (moving cards, inline edits) use small JSON endpoints behind the same session and Policies.

## Consequences

- Positive: one codebase and one pipeline. Authorization is defined once in Policies. Wayfinder generates typed route helpers for TypeScript.
- Negative: there is no public API for third-party or mobile clients.
- Follow-up: if a public API is needed, add `routes/api.php` with Sanctum that reuses the same Actions and Policies.
