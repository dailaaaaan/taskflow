# 0002. Use Pusher Channels for real-time updates

- **Status:** Accepted
- **Date:** 2026-10-29

## Context

Board changes must reach the other members within a second, and the app must show who is currently viewing a board. Hosting must be free, and free tiers do not provide always-on background processes.

## Options considered

1. **Laravel Reverb (self-hosted WebSocket server).** First-party and free software, but it needs a long-running process that free hosting tiers do not offer reliably.
2. **Socket.io with a Node server.** A second runtime and a second deployment, outside Laravel's broadcasting system.
3. **Pusher Channels (managed).** Its free tier covers a portfolio project. Laravel supports it natively as a broadcasting driver, and it supports private and presence channels.

## Decision

Pusher Channels, used in both development and production through Laravel broadcasting and Laravel Echo. Events implement `ShouldBroadcastNow`, so no queue worker is required.

## Consequences

- Positive: no extra infrastructure. Channel authorization reuses `BoardPolicy`. Presence channels provide "who is viewing" for free.
- Negative: dependency on a third-party service and its free-tier limits. Broadcasting synchronously adds a little latency to the request that triggers it.
- Follow-up: because the code only uses Laravel's broadcasting abstraction, switching to Reverb later is a configuration change.
