# Deployment — Dokploy on One VPS

> Production target: one VPS managed by Dokploy.
> Dokploy owns the host-level Traefik and public ports 80/443.
> The project Docker Compose MUST NOT contain its own Traefik service.

## Host layout
```text
VPS
├── Dokploy
│   └── managed Traefik :80/:443
└── Video Calls Compose
    ├── app
    ├── worker
    ├── scheduler
    ├── reverb
    ├── postgres
    ├── redis
    └── livekit
```

## DNS
Point:
```text
call.example.com
ws.call.example.com
livekit.example.com
```
to the VPS public IP.

## Dokploy setup
Create a project/environment and a Docker Compose service connected to the production Git branch.

Configure domains:
```text
call.example.com      -> app:<FrankenPHP/Octane internal HTTP port>
ws.call.example.com   -> reverb:8080
livekit.example.com   -> livekit:7880
```
Enable HTTPS for all public domains.

Do not expose those internal HTTP ports through host `ports:` solely for routing.

Implemented Compose services:
```text
app        -> internal 8000
worker     -> no public port
scheduler  -> no public port
reverb     -> internal 8080
postgres   -> no public port
redis      -> no public port
livekit    -> internal 7880 plus direct media ports
```

Local WSL2/Sail development remains available through:
```bash
SAIL_FILES=compose.sail.yaml ./vendor/bin/sail up -d
```

## Environment
Store production secrets in Dokploy, not Git. Required categories include Laravel app, PostgreSQL, Redis, Reverb and LiveKit values.

Do not reuse a local Sail `.env` as the Dokploy source of truth. The production `compose.yaml` pins internal service hosts and runtime drivers so local development values such as `DB_HOST=pgsql` cannot redirect production containers. Dokploy still must provide secrets and public domains explicitly.

Internal service addresses:
```text
DB_HOST=postgres
REDIS_HOST=redis
```

PostgreSQL 18 Docker images store data under a versioned subdirectory. Keep the named volume mounted at `/var/lib/postgresql`; do not move it to `/var/lib/postgresql/data`.

Public Reverb host should be the WSS domain. Keep internal/public host variables separate where the framework requires it.

Required Dokploy variables:
```text
APP_NAME
APP_ENV=production
APP_KEY
APP_DEBUG=false
APP_URL=https://call.example.com
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE
DB_USERNAME
DB_PASSWORD
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
BROADCAST_CONNECTION=reverb
OCTANE_SERVER=frankenphp
OCTANE_HTTPS=true
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080
REVERB_HOST=ws.call.example.com
REVERB_PORT=443
REVERB_SCHEME=https
REVERB_APP_ID
REVERB_APP_KEY
REVERB_APP_SECRET
REVERB_SCALING_ENABLED=true
VITE_APP_NAME
VITE_REVERB_APP_KEY
VITE_REVERB_HOST=ws.call.example.com
VITE_REVERB_PORT=443
VITE_REVERB_SCHEME=https
LIVEKIT_URL=https://livekit.example.com
LIVEKIT_API_KEY
LIVEKIT_API_SECRET
LIVEKIT_TOKEN_TTL=3600
```

## Firewall
Allow:
```text
80/TCP
443/TCP
7881/TCP
50000-60000/UDP
3478/UDP
```

Keep `50000-60000/UDP` for the production VPS. For local Windows 10 WSL2/Docker Desktop smoke checks, use `LIVEKIT_RTC_UDP_PORT_RANGE=50000-50020`; Compose injects that same range into Docker port publishing and the generated LiveKit runtime config. Do not deploy the reduced range to production.

Optionally after verified TURN/TLS setup:
```text
5349/TCP
```

Do not publicly expose:
```text
5432
6379
```

## 443 ownership
Public `443/TCP` belongs to Dokploy-managed Traefik. LiveKit must not bind the same host port in this single-VPS topology.

## Application runtime
Use FrankenPHP + Laravel Octane, never `artisan serve`.

Worker is a separate service running `queue:work`. Scheduler is a separate service running `schedule:work`. Reverb is a separate service listening internally on 8080.

## LiveKit
Signaling listens internally on 7880 and is routed by Dokploy. Only required media/TURN ports are directly published.

Pin a production LiveKit image version rather than permanently using `latest`.

The LiveKit container image has `/livekit-server` as its default entrypoint. The Compose service overrides the entrypoint to `/bin/sh -ec` so it can generate `/tmp/livekit.yaml` from environment variables before executing `/livekit-server --config /tmp/livekit.yaml`.

## Migrations
Do not run unconditional migrations in every long-running container startup command.

Use an explicit release/deploy step or one-shot migration task:
```bash
php artisan migrate --force
```
only after confirming production environment.

For this Compose layout, run migrations in the built application container as an explicit one-shot task after deploy:
```bash
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan event:cache
```

## Deploy flow
1. Push production branch.
2. Dokploy builds/deploys Compose.
3. Run explicit migration step.
4. Verify app, DB, Redis, worker, scheduler and Reverb.
5. Verify LiveKit signaling/media.
6. Run two-browser smoke test.

## Backups
Back up PostgreSQL regularly with retention, off-server copy and documented restore.

## Rollback
Retain previous deployable revision/image. Prefer backward-compatible migrations. Never blindly `migrate:rollback` in production.
