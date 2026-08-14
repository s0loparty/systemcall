# Video Calls — Implementation Plan

> Production target: one VPS managed by Dokploy.
> Dokploy owns the host-level Traefik and public ports 80/443.
> The project Docker Compose MUST NOT contain its own Traefik service.

Status: `[ ]` not started, `[-]` in progress, `[x]` complete, `[!]` blocked.

## Repository assessment
Before implementation inspect and record Laravel/PHP/Vue versions, package manager, auth, schema, Docker, Redis, queues, Reverb, tests, CI and environment conventions.

Current assessment:
- Laravel `13.25.0`; local PHP CLI `8.3.6`; Composer constraint remains `^8.3` for local/CI compatibility, while production Docker image targets PHP `8.4`.
- Vue `3.5.13`, Inertia Laravel `3.3.1`, Inertia Vue `3.x`, Vite `8.x`, TailwindCSS `4.x`, Pest `4.7.x`.
- Package manager: npm with `package-lock.json`; local development is Windows 10 WSL2 + Laravel Sail. The original Sail compose is preserved as `compose.sail.yaml`.
- Auth exists via Laravel Fortify + Inertia Vue, with registration, login, password reset, email verification routes, password confirmation, and 2FA UI/actions.
- Existing schema only covers users, sessions, cache, jobs, failed jobs, job batches, password reset tokens, and Fortify 2FA columns. No video-call domain tables/models exist yet.
- Before Milestone 0, Docker was Sail-only (`laravel.test`, `pgsql`, `redis`) with bind mounts and public PostgreSQL/Redis ports.
- Redis config existed but app defaults were database-backed cache/session/queue in `.env.example`.
- Reverb and Octane were not installed before Milestone 0. They are now installed for the infrastructure runtime.
- Frontend is Vue 3 + TypeScript under `resources/js`, with generated Wayfinder routes/actions.
- Tests exist under Pest feature/unit suites. CI runs PHP 8.3, Node 22, `composer setup`, then `composer ci:check`.
- Production `compose.yaml` is intentionally not Sail compose. It pins internal service hosts/drivers for production (`DB_HOST=postgres`, `REDIS_HOST=redis`, Redis cache/session/queue, Reverb broadcasting) so a local Sail `.env` cannot silently redirect production containers to Sail service names such as `pgsql`.
- PostgreSQL uses image `postgres:18-alpine`; its volume target is `/var/lib/postgresql`, not `/var/lib/postgresql/data`, to match PostgreSQL 18 Docker image layout.

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

### Milestone 0 — Dokploy-ready infrastructure `[x]`
- [x] Production Dockerfile with PHP 8.4 + FrankenPHP + Octane
- [x] Docker Compose without Traefik
- [x] app/worker/scheduler/reverb share application image
- [x] PostgreSQL + named volume + healthcheck
- [x] Redis + healthcheck
- [x] Reverb internal port 8080
- [x] LiveKit signaling internal port 7880
- [x] Direct LiveKit media ports
- [x] LiveKit config
- [x] `.env.example`
- [x] Dokploy domain mapping docs
- [x] Dokploy environment-variable docs
- [x] firewall docs
- [x] explicit migration/deploy procedure
- [x] PostgreSQL/Redis not publicly exposed

Acceptance:
- [x] `docker compose config` succeeds
- [x] no project Traefik service
- [x] app uses FrankenPHP/Octane
- [x] app reaches Postgres/Redis
- [x] worker/scheduler/Reverb/LiveKit start
- [x] docs state Dokploy owns 80/443

Production image build blockers found during verification were fixed:
- assets stage now uses PHP 8.4-compatible base tooling;
- assets stage copies `composer.json`/`composer.lock` because Wayfinder/Laravel bootstrap reads them;
- assets stage creates Laravel runtime directories before Wayfinder/Vite build.

Earlier local WSL2/Docker Desktop verification on 2026-08-14 built the production image and passed `docker compose config`, but startup did not satisfy acceptance: Compose reported `postgres` failed health/dependency startup and then Docker commands began hanging after LiveKit startup with the required large UDP media range (`50000-60000/UDP`). This was superseded by the final WSL2-safe verification below.

Re-check after Docker Desktop restart on 2026-08-14:
- fixed PostgreSQL 18 volume mount target from `/var/lib/postgresql/data` to `/var/lib/postgresql`;
- clean local project `COMPOSE_PROJECT_NAME=systemcall-m0check` built the production image successfully;
- clean startup reached `Started` for `app`, `worker`, `scheduler`, `reverb`, and `livekit`, with `postgres` and `redis` reported `Healthy` during `compose up`;
- Docker API then hung on follow-up `docker compose ps`/`docker compose logs`, so `app reaches Postgres/Redis` could not be asserted from inside the `app` container. This was superseded by the final WSL2-safe verification below.

Re-check after full Docker restart on 2026-08-14:
- local Sail stack works: Laravel `/up` returns `200`, migrations are reachable, Redis ping works, Pest tests pass, TypeScript check passes, ESLint passes, and Vite build passes;
- production verification project `COMPOSE_PROJECT_NAME=systemcall-m0verify` rebuilt the production image successfully;
- during production `compose up`, `postgres` and `redis` reached `Healthy`, and `app`, `worker`, `scheduler`, and `reverb` reached `Started`;
- follow-up `docker compose ps` and `docker compose exec -T app ...` hung with no output after LiveKit startup/required UDP media range, so `app reaches Postgres/Redis` and full service status still could not be asserted. This was superseded by the final WSL2-safe verification below.

LiveKit-specific diagnosis on 2026-08-14:
- root cause 1: `livekit/livekit-server:v1.13.4` has `/livekit-server` as its default entrypoint, so the Compose shell command was being passed to LiveKit as invalid CLI flags. Fixed by overriding the service entrypoint to `/bin/sh -ec` and executing `/livekit-server --config /tmp/livekit.yaml` after generating config;
- root cause 2: Windows 10 WSL2/Docker Desktop can hang when publishing the production `50000-60000/UDP` range locally. Production keeps the full range, but local smoke checks may set `LIVEKIT_RTC_UDP_PORT_RANGE=50000-50020`;
- isolated smoke check passed with `COMPOSE_PROJECT_NAME=livekit-smallcheck LIVEKIT_RTC_UDP_PORT_RANGE=50000-50020 docker compose up -d redis livekit`;
- LiveKit logs confirmed `portHttp: 7880`, `rtc.portTCP: 7881`, and `rtc.portICERange: [50000, 50020]`;
- `docker compose exec -T livekit /livekit-server ports --config /tmp/livekit.yaml` confirmed HTTP 7880, ICE/TCP 7881, ICE/UDP 50000-50020, TURN/UDP 3478.

Final local WSL2-safe Milestone 0 verification on 2026-08-14:
- full production Compose stack passed with `COMPOSE_PROJECT_NAME=livekit-smallcheck LIVEKIT_RTC_UDP_PORT_RANGE=50000-50020 docker compose up -d --build --force-recreate`;
- services `app`, `worker`, `scheduler`, `reverb`, `postgres`, `redis`, and `livekit` were `Up`; `app`, `postgres`, and `redis` were healthy where healthchecks are expected;
- `worker`, `scheduler`, and `reverb` now disable the inherited FrankenPHP image healthcheck because they are command services, not the Octane HTTP app;
- `app` reached Postgres and Redis through Laravel bootstrap and returned `app-db-redis-ok`;
- app logs no longer show Caddy autosave permission errors after granting `www-data` write access to `/config` and `/data` in the production image.

Production VPS/Dokploy still must be verified with the full `LIVEKIT_RTC_UDP_PORT_RANGE=50000-60000`. Do not use the reduced WSL2 smoke-check range in production.

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
