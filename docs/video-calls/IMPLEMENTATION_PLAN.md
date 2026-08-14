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

### Milestone 1 — Domain model `[x]`
Room, RoomMember, RoomBan, WaitingRoomMember, RoomMessage, RoomSubroom; migrations, relations, indexes, enums, factories and policies.

Implemented:
- [x] `rooms` table with owner, public id, visibility, status, password hash placeholder, waiting-room flag, max participants, ended timestamp and indexes
- [x] `room_members` table with user/guest identity fields, role, LiveKit identity, media publish permission flags, join/leave timestamps and indexes
- [x] `room_bans` table with user/guest ban target, moderator reference, reason, expiry and indexes
- [x] `waiting_room_members` table with user/guest identity fields, status, moderator decision fields and indexes
- [x] `room_messages` table with room/subroom/member references, type, body, JSON metadata and edit/delete timestamps
- [x] `room_subrooms` table with public id, LiveKit room name, position/default flag and indexes
- [x] enum casts for room status, room visibility, room role and waiting-room status
- [x] Eloquent relationships on video-call models plus inverse user relationships
- [x] factories for all video-call domain models
- [x] policies for baseline owner/moderator/member boundaries
- [x] feature tests covering persistence, casts, relationships, cascade behavior and policies

Milestone 1 verification on 2026-08-14:
- `docker compose config --services` passed
- `vendor/bin/pint --dirty --format agent` passed
- `composer run types:check` passed
- `SAIL_FILES=compose.sail.yaml ./vendor/bin/sail artisan test --compact tests/Feature/VideoCallDomainModelTest.php` passed: 2 tests, 37 assertions
- `SAIL_FILES=compose.sail.yaml ./vendor/bin/sail artisan test --compact` passed: 41 tests, 165 assertions
- `composer validate --strict` passed
- `npm run types:check` passed
- `npm run lint:check` passed
- `npm run build` passed

Notes:
- Room CRUD/access, password hashing behavior, server-verifiable guest sessions, LiveKit token issuance, waiting-room workflow and Reverb events remain in later milestones.
- Policies for nested resources intentionally keep plain `create` denied until Milestone 2+ introduces room-scoped requests/services with explicit authorization context.

### Milestone 2 — Room CRUD/access `[x]`
Secure public IDs, public/private rooms, hashed password, owner authorization, server-verifiable guest identity.

Implemented:
- [x] public-id route model binding for `Room`; numeric ids remain internal
- [x] authenticated room CRUD endpoints: `rooms.index`, `rooms.store`, `rooms.update`, `rooms.destroy`
- [x] public room show endpoint: `rooms.show`
- [x] room membership join endpoint: `rooms.memberships.store`
- [x] room creation generates unpredictable `public_id`
- [x] owner membership is created automatically with `owner` role
- [x] room passwords are stored only as hashes
- [x] public rooms can be viewed by guests
- [x] private rooms cannot be viewed by guests
- [x] private/password-protected rooms can be joined only with correct password
- [x] private rooms without password remain members-only
- [x] guests receive a server-issued encrypted/httpOnly cookie identity
- [x] browser-supplied `guest_id` input is ignored
- [x] owner-only update/delete authorization enforced

Milestone 2 verification on 2026-08-14:
- `SAIL_FILES=compose.sail.yaml ./vendor/bin/sail artisan test --compact tests/Feature/RoomCrudAccessTest.php` passed: 3 tests, 33 assertions
- `SAIL_FILES=compose.sail.yaml ./vendor/bin/sail artisan test --compact` passed: 44 tests, 198 assertions
- `vendor/bin/pint --dirty --format agent` passed
- `composer run types:check` passed
- `composer validate --strict` passed
- `npm run types:check` passed
- `npm run lint:check` passed
- `npm run build` passed
- `docker compose config --services` passed

Notes:
- Endpoints currently return JSON and are ready for later Inertia/frontend integration.
- LiveKit token issuance, media connection, waiting-room workflow, moderation operations and Reverb events remain in later milestones.

### Milestone 3 — Basic LiveKit vertical slice `[x]`
Backend LiveKit service/token issuance plus frontend connect/disconnect, mic, camera and participant grid.

Implemented:
- [x] installed PHP LiveKit token SDK `agence104/livekit-server-sdk`
- [x] installed browser SDK `livekit-client`
- [x] central `LiveKitTokenService` signs room-scoped JWTs from environment-only credentials
- [x] token endpoint `POST /rooms/{public_id}/livekit-token`
- [x] token issuance requires an active `RoomMember` resolved from authenticated user or server-issued guest cookie
- [x] token issuance rejects inactive rooms, non-members, banned participants and waiting-room rooms
- [x] LiveKit identity uses backend-controlled `room_members.livekit_identity`
- [x] token grant uses `roomJoin`, room name `room-{public_id}`, subscribe permission and publish-source restrictions from `RoomMember`
- [x] Inertia page `GET /rooms/{public_id}/call`
- [x] frontend joins membership, requests token, connects to LiveKit, publishes mic/camera, disconnects, toggles mic/camera and renders participant media grid
- [x] frontend uses Wayfinder-generated route URLs and Inertia `useHttp` for JSON requests

Acceptance:
- [x] unauthorized users cannot obtain a token
- [x] backend token payload maps to the expected LiveKit room and RoomMember identity
- [x] frontend contains connect/disconnect, mic, camera and participant grid vertical slice
- [!] two independent browser sessions can see/hear each other: implemented but requires a running LiveKit server and manual two-browser smoke test in local/prod environment

Milestone 3 verification on 2026-08-14:
- `SAIL_FILES=compose.sail.yaml ./vendor/bin/sail artisan test --compact tests/Feature/LiveKitVerticalSliceTest.php` passed: 5 tests, 37 assertions
- `SAIL_FILES=compose.sail.yaml ./vendor/bin/sail artisan test --compact` passed: 49 tests, 235 assertions
- `vendor/bin/pint --dirty --format agent` passed
- `SAIL_FILES=compose.sail.yaml ./vendor/bin/sail composer run types:check` passed
- `npm run types:check` passed
- `npm run lint:check` passed after eslint autofix
- `npm run build` passed
- `docker compose config --services` passed
- `composer validate --strict` passed

Notes:
- Local host `composer run types:check` currently fails before analysis with `Failed to listen on "tcp://127.0.0.1:0": Success`; the same PHPStan script passes inside Sail.
- LiveKit token issuance is ready for Milestone 4 media UX work, but screen sharing/device selection/error states remain in later milestones.

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
