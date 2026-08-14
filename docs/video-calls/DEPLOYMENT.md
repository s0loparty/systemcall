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

## Environment
Store production secrets in Dokploy, not Git. Required categories include Laravel app, PostgreSQL, Redis, Reverb and LiveKit values.

Internal service addresses:
```text
DB_HOST=postgres
REDIS_HOST=redis
```

Public Reverb host should be the WSS domain. Keep internal/public host variables separate where the framework requires it.

## Firewall
Allow:
```text
80/TCP
443/TCP
7881/TCP
50000-60000/UDP
3478/UDP
```

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

## Migrations
Do not run unconditional migrations in every long-running container startup command.

Use an explicit release/deploy step or one-shot migration task:
```bash
php artisan migrate --force
```
only after confirming production environment.

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
