# Bugsink monitoring (Laravel 13 + Vue 3)

This branch integrates the official Sentry-compatible SDKs with a **self-hosted Bugsink** server. No Sentry SaaS account is required.

## Build status

The Composer and npm lock files include the SDK dependencies. Before deploying, confirm that the intended commit has a successful CI run and Docker build. GitHub Actions builds and publishes the image to GHCR; Dokploy pulls that image instead of building it on the VPS. Check the image tag or digest before deploying.

The Dockerfile uses `composer install` and `npm ci`; keep both lock files updated whenever SDK versions change.

## Bugsink projects

Create **two projects** in Bugsink, one for PHP/Laravel and one for JavaScript/Vue, and copy each project's DSN.

Set in the **Systemcall Compose environment** in Dokploy (forwarded to app, worker, scheduler and reverb):

```dotenv
SENTRY_LARAVEL_DSN=https://PUBLIC_KEY@errors.example.com/1
SENTRY_TRACES_SAMPLE_RATE=0
```

Set the following **GitHub Actions repository variable** under Settings > Secrets and variables > Actions > Variables:

```dotenv
VITE_SENTRY_DSN=https://PUBLIC_KEY@errors.example.com/2
```

The image publication workflow passes this public frontend DSN to the Docker build. The Dockerfile exposes `ARG VITE_SENTRY_DSN` in the assets stage. For a local Docker build, pass it with `--build-arg VITE_SENTRY_DSN=...`.

A Vite variable set only in Dokploy or at container runtime will not change the already built browser bundle. After changing the Actions variable, build and publish a new image, verify it, then deploy it. An unset or empty variable keeps browser monitoring disabled; do not use the backend project DSN here.

When synchronizing branches, merge the integration PR into `main` first and check CI and the GHCR image. Then promote `main` to `production` with a merge PR (not squash/rebase, so both histories stay connected). A push to `production` can trigger Dokploy after successful CI/image publication if `DOKPLOY_DEPLOY_ENABLED=true`; do not merge the promotion before validation. Merge any resulting production merge commit back into `main` if needed to keep the trees aligned.

Use HTTPS for public ingestion. Browser-side requests require that Bugsink/proxy accept cross-origin Sentry envelope requests. Use distinct project DSNs for backend and frontend.

## Behavior

- Unhandled Laravel exceptions (including API errors) are sent to Bugsink.
- Vue errors are captured by `@sentry/vue` once the Inertia Vue app is mounted.
- Browser capture is enabled **only for production builds** when `VITE_SENTRY_DSN` is set.
- No tracing or session replay is enabled. This intentionally minimizes ingestion, CPU and disk usage.
- Standard Laravel `Log::info()` entries stay in the configured Laravel/Dokploy log channel; Bugsink is error tracking, not a general log archive.
- Keep credentials, session cookies, tokens, request bodies and other sensitive data out of custom exception context. DSNs are identifiers, not administrator passwords, but still avoid exposing backend configuration unnecessarily.

## Verify

Trigger a test exception in a safe local/staging environment, or temporarily call `\\Sentry\\captureMessage('Systemcall backend test');` from Tinker after setting a backend DSN. For Vue, temporarily call `Sentry.captureMessage('Systemcall frontend test')` from application code after initialization. Remove temporary test instrumentation before deploying.

If a project receives no events, check the backend container's outbound network, Bugsink's ingestion endpoint, browser DevTools Network/CORS, and the Vite build-time DSN.

## Optional source maps

Production source maps are not enabled by this change. Uploading private source maps requires a separate release/artifact configuration; enabling `build.sourcemap` without private uploads can expose source code to visitors.
