# Video Calls — Implementation Plan

> Production target: one VPS managed by Dokploy.
> Dokploy owns the host-level Traefik and public ports 80/443.
> The project Docker Compose MUST NOT contain its own Traefik service.

Status: `[ ]` not started, `[-]` in progress, `[x]` complete, `[!]` blocked.

## Repository assessment
Before implementation inspect and record Laravel/PHP/Vue versions, package manager, auth, schema, Docker, Redis, queues, Reverb, tests, CI and environment conventions.

## Target services
```text
app
worker
scheduler
reverb
postgres
redis
livekit
```
No project-level Traefik.

## Target routing
```text
call.example.com      -> app:<internal HTTP port>
ws.call.example.com   -> reverb:8080
livekit.example.com   -> livekit:7880
```

Direct media:
```text
7881/TCP
50000-60000/UDP
3478/UDP
```

### Milestone 0 — Dokploy-ready infrastructure `[ ]`
- [ ] Production Dockerfile with PHP 8.4 + FrankenPHP + Octane
- [ ] Docker Compose without Traefik
- [ ] app/worker/scheduler/reverb share application image
- [ ] PostgreSQL + named volume + healthcheck
- [ ] Redis + healthcheck
- [ ] Reverb internal port 8080
- [ ] LiveKit signaling internal port 7880
- [ ] Direct LiveKit media ports
- [ ] LiveKit config
- [ ] `.env.example`
- [ ] Dokploy domain mapping docs
- [ ] Dokploy environment-variable docs
- [ ] firewall docs
- [ ] explicit migration/deploy procedure
- [ ] PostgreSQL/Redis not publicly exposed

Acceptance:
- [ ] `docker compose config` succeeds
- [ ] no project Traefik service
- [ ] app uses FrankenPHP/Octane
- [ ] app reaches Postgres/Redis
- [ ] worker/scheduler/Reverb/LiveKit start
- [ ] docs state Dokploy owns 80/443

### Milestone 1 — Domain model `[ ]`
Room, RoomMember, RoomBan, WaitingRoomMember, RoomMessage, RoomSubroom; migrations, relations, indexes, enums, factories and policies.

### Milestone 2 — Room CRUD/access `[ ]`
Secure public IDs, public/private rooms, hashed password, owner authorization, server-verifiable guest identity.

### Milestone 3 — Basic LiveKit vertical slice `[ ]`
Backend LiveKit service/token issuance plus frontend connect/disconnect, mic, camera and participant grid.

Acceptance: two independent browser sessions can see/hear each other and unauthorized users cannot obtain a token.

### Milestone 4 — Media UX `[ ]`
Device selection, preview, screen share, active speaker, adaptive layout, permission/device errors, reconnect UI, adaptive stream/simulcast/dynacast review.

### Milestone 5 — Reverb `[ ]`
Authorized application channels, frontend Echo, application events and reconnect. Never replace LiveKit media presence with Reverb.

### Milestone 6 — Waiting room `[ ]`
Request, realtime waiting list, approve/reject, token only after approval, stale cleanup and tests.

### Milestone 7 — Roles/moderation `[ ]`
Promote/demote, mute/mute-all, kick, ban, owner-only deletion and authorization tests. No silent remote unmute.

### Milestone 8 — Chat/hands/pinning `[ ]`
Safe rate-limited chat, raised hands, local personal pin and authorized Reverb-synchronized global pin.

### Milestone 9 — Subrooms `[ ]`
Maximum 5, separate LiveKit room per subroom, separately authorized target token and clean switching.

### Milestone 10 — Production hardening `[ ]`
Rate limits, LiveKit webhook verification/idempotency, scheduler cleanup, health checks, reconnect edge cases, security audit, Dokploy deployment verification, firewall, backup/rollback and TURN testing.

## Accepted deployment decisions
- Dokploy is production deployment platform.
- Initial production is one VPS.
- Dokploy-managed Traefik owns 80/443.
- Project Compose has no Traefik service.
- LiveKit media ports bypass HTTP reverse proxy.
- TURN/TLS on host 443 is deferred while Dokploy owns 443.
- Optional 5349 TURN/TLS requires version-specific verification.
