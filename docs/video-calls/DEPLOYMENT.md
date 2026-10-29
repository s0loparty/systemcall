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
Create a project/environment and a Docker Compose service connected to the `production` Git branch, using `compose.yaml`.

The production Compose file only pulls images; it does not build on the VPS. Set
`APP_IMAGE=ghcr.io/s0loparty/systemcall:production` in Dokploy before deploying.
The `app`, `worker`, `scheduler` and `reverb` services use this same image and
`pull_policy: always`, so a deployment fetches the current published tag.

Disable Git push Auto Deploy in Dokploy. Otherwise Dokploy can deploy before
GitHub finishes testing and publishing the new image. Deploy manually after the
publication job succeeds, or enable the GitHub-triggered deployment below.

Keep the existing Compose project/service and named volumes when switching to
registry images. Do not recreate the project or remove PostgreSQL/Redis volumes.

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

Frontend Echo connects to the public Reverb WSS domain from `VITE_REVERB_*` values.
These values are build-time inputs for the production Vite bundle: configure them
in GitHub Actions repository variables as described below and publish a new image
after changing them. Changing only Dokploy's runtime `VITE_*` variables does not
change the built bundle. Room application-channel authorization is still an HTTP
request to the Laravel app at `/broadcasting/room-auth`; route `call.example.com`
to `app:8000` before testing private room channels.

Required Dokploy variables:
```text
APP_IMAGE=ghcr.io/s0loparty/systemcall:production
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
VIDEO_CALL_WAITING_ROOM_STALE_MINUTES=30
```

`DB_PASSWORD` is mandatory for production. The official PostgreSQL image
refuses to initialize with an empty `POSTGRES_PASSWORD`, so Dokploy must provide
a real generated password before first deploy.

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
1. Push `production` (or first verify the image on `main`).
2. GitHub Actions runs app checks and browser smoke tests.
3. Actions builds the production Dockerfile on a GitHub runner with BuildKit caching.
4. Actions checks PHP extensions, built frontend assets and the running Octane `/up` endpoint before publishing to GHCR.
5. Dokploy pulls the published image and starts Compose, either manually or through the optional Actions deployment job.
6. Run the explicit migration step.
7. Verify app, DB, Redis, worker, scheduler and Reverb, then LiveKit signaling/media and a two-browser smoke test.

### GitHub image publication

The existing `CI` workflow calls `.github/workflows/publish-image.yml` after app
checks and browser smoke pass. Publication runs on pushes to `main` and
`production`, or a manual CI run on either branch. Pull requests and scheduled
runs do not publish or deploy. Full LiveKit E2E retains its existing manual/weekly
schedule; it is not a publication gate.

Set **Settings > Secrets and variables > Actions > Variables**:

| Variable | Value |
| --- | --- |
| `VITE_APP_NAME` | `Systemcall` (default) |
| `VITE_REVERB_APP_KEY` | Public Reverb app key, matching Dokploy's `REVERB_APP_KEY` (required) |
| `VITE_REVERB_HOST` | Public WSS hostname, e.g. `ws.call.example.com` (required) |
| `VITE_REVERB_PORT` | `443` (default) |
| `VITE_REVERB_SCHEME` | `https` (default) |

These values are public frontend configuration, not backend secrets. Never put
`APP_KEY`, database passwords, `REVERB_APP_SECRET` or `LIVEKIT_API_SECRET` in build
arguments. Backend secrets remain in Dokploy and enter containers at runtime.

GitHub's automatic `GITHUB_TOKEN` publishes with job-scoped `packages: write`;
no registry PAT is needed in Actions. Ensure repository/organization policy
allows package publication. Images target `linux/amd64`, matching the x86 VPS.

Published tags:

```text
ghcr.io/s0loparty/systemcall:main
ghcr.io/s0loparty/systemcall:production
ghcr.io/s0loparty/systemcall:sha-<full-commit-sha>
```

Only the corresponding branch tag is updated on each run. The Actions job summary
also records the image digest; use `ghcr.io/s0loparty/systemcall@sha256:<digest>`
as `APP_IMAGE` to pin an exact image. A rebuild of the same commit can produce a
different digest because base images and public build configuration can change.

### Dokploy registry access

For a private GHCR package, configure a `ghcr.io` registry credential in Dokploy
using your GitHub username and a PAT (classic) with `read:packages`, authorized to
read the package. Attach/use the credential for this Compose deployment according
to your Dokploy version. Alternatively, make the package public to allow
anonymous pulls. Do not put registry credentials in `compose.yaml` or `APP_IMAGE`.

Configure `APP_IMAGE` as above and keep the existing domain mappings and backend
network. If a custom Compose command is configured in Dokploy, remove build
options; preserve Dokploy's actual project name and Compose path and use
`up -d --no-build --remove-orphans` as its final arguments. `pull_policy: always`
fetches the Laravel image even when the branch tag is already cached locally.

### Optional automatic deployment

After manually verifying the first registry-based deployment, set these GitHub
Actions repository variables:

```text
DOKPLOY_DEPLOY_ENABLED=true
DOKPLOY_URL=https://your-dokploy-instance.example.com
DOKPLOY_COMPOSE_ID=<existing-compose-service-id>
```

Add `DOKPLOY_API_KEY` as an Actions **secret**, using a Dokploy API token with
permission to deploy that Compose service. The runner must be able to reach the
Dokploy API over HTTPS.

Only a successful `production` image publication requests
`POST /api/compose.deploy`. `main` never triggers this deployment. The API request
queues the deployment; its success is not proof that the containers are healthy.
Check Dokploy's deployment logs and perform the explicit migration/verification
steps. The job does not execute migrations or delete/recreate volumes.

Without `DOKPLOY_DEPLOY_ENABLED=true`, images are still published, and deployment
is manual. Leave Git push Auto Deploy disabled in both modes.

References: [GitHub Container Registry](https://docs.github.com/en/packages/working-with-a-github-packages-registry/working-with-the-container-registry),
[Docker test-before-push](https://docs.docker.com/build/ci/github-actions/test-before-push/),
[Dokploy Compose API](https://docs.dokploy.com/docs/api/compose).

Local verification on 2026-10-23: deployment configuration tests passed (3 tests,
31 assertions), Pint and actionlint passed, and standalone Docker Compose
`config --quiet` passed with a placeholder image/password. The production image
build/runtime smoke and GHCR publication were not executed locally: Docker Engine
is unavailable in this WSL distro (Docker reports missing WSL integration).
The workflow performs those checks on its GitHub runner; the first publication
and actual Dokploy deployment still need verification after configuration.

## Backups
Back up PostgreSQL regularly with retention, off-server copy and documented restore.

## Rollback
Retain previous deployable revision/image. To roll back application code, set
`APP_IMAGE` to a previously verified digest from an Actions job summary and deploy
again. Disable automatic deployment while pinned for rollback, then restore the
`production` tag when ready to resume releases. Prefer backward-compatible
migrations. Never blindly `migrate:rollback` in production.

## LiveKit webhook deployment requirement
Abnormal disconnect cleanup requires the self-hosted LiveKit server to send signed webhooks to Laravel:

```text
https://call.example.com/webhooks/livekit
```

Add the following to the generated LiveKit server configuration (using the actual production app domain):

```yaml
webhook:
  api_key: "${LIVEKIT_API_KEY}"
  urls:
    - "https://call.example.com/webhooks/livekit"
```

The configured `api_key` must match a key in LiveKit's `keys` configuration and Laravel must have the corresponding `LIVEKIT_API_KEY` / `LIVEKIT_API_SECRET`, because Laravel verifies the webhook JWT and request-body SHA-256 signature.

No extra firewall port is required: webhook delivery goes from the LiveKit container/server to the public Laravel HTTPS domain on `443/TCP` through Dokploy-managed Traefik.

After deploying the migration that adds `room_members.media_session_id`, verify with two browsers:

1. join from browser B;
2. close browser B without pressing Leave;
3. wait until LiveKit emits `participant_left` after its reconnect/disconnect handling;
4. confirm browser A removes B from participants/subroom management without a page reload;
5. repeat with temporary network loss and verify a successful LiveKit reconnect does not remove B;
6. switch B between main room/subrooms and verify delayed leave events from the old LiveKit room do not mark B as left.
