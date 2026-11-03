# Bugsink monitoring (Laravel 13 + Vue 3)

This branch integrates the official Sentry-compatible SDKs with a **self-hosted Bugsink** server. No Sentry SaaS account is required.

## Build status

The Composer and npm lock files have now been regenerated and committed to this branch. GitHub Actions CI passed on commit `eb9167a0` (see the branch Checks tab). Before deploying, confirm that the merged commit has a successful CI run and that Dokploy builds the intended branch or image. Do not configure public DSNs or production monitoring on a staging container accidentally.

The Dockerfile uses `composer install` and `npm ci`; keep both lock files updated whenever SDK versions change.

## Bugsink projects

Create **two projects** in Bugsink, one for PHP/Laravel and one for JavaScript/Vue, and copy each project's DSN.

Set on the **Systemcall application** in Dokploy:

```dotenv
SENTRY_LARAVEL_DSN=https://PUBLIC_KEY@errors.example.com/1
SENTRY_TRACES_SAMPLE_RATE=0
```

Configure the following **Docker build argument** as well as the deployment environment variable (it is used by Vite at build time):

```dotenv
VITE_SENTRY_DSN=https://PUBLIC_KEY@errors.example.com/2
```

A Vite variable set only at container runtime will not change the already built browser bundle. The Dockerfile exposes `ARG VITE_SENTRY_DSN` in the assets stage.

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
