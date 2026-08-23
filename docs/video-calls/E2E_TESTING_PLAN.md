# Video Calls — Browser E2E Testing Plan

> Цель: автоматизировать браузерную проверку пользовательских сценариев
> комнат, аудио, видео, screen sharing и поднятия руки.
>
> Тесты не должны подменять backend feature/unit suite. Они проверяют то, что
> нельзя надежно доказать без реального браузера: Inertia navigation, media
> permissions, DOM media layout, Reverb events, LiveKit WebRTC state and
> multi-user behavior.

Status: `[ ]` not started, `[-]` in progress, `[x]` complete, `[!]` blocked.

## Current assessment
- Pest `4.7.8` and `pestphp/pest-plugin-laravel` are installed.
- `pestphp/pest-plugin-browser` is installed.
- `playwright` is installed.
- Browser tests directory exists: `tests/Browser`.
- Current backend coverage already validates room domain, access, waiting room,
  LiveKit token issuance, moderation, Reverb application events and subrooms.
- Current frontend checks are static only: `npm run types:check`,
  `npm run lint:check`, `npm run build`.
- Local development target is Windows 10 WSL2 + Laravel Sail.
- Real media E2E requires running Laravel, Vite, Reverb and LiveKit together.

## Target tooling
Preferred stack:
- Pest Browser plugin for Laravel-integrated browser tests.
- Playwright as the browser driver through Pest Browser.
- Existing Sail environment for local execution.
- Reduced LiveKit UDP range for WSL2 local smoke runs when needed.

Required setup:
- [ ] install `pestphp/pest-plugin-browser --dev`
- [ ] install `playwright`
- [ ] install Playwright browser binaries
- [ ] add `tests/Browser`
- [ ] add browser screenshots/artifacts paths to `.gitignore`
- [ ] add npm/composer scripts for browser tests
- [ ] document local Sail commands
- [ ] document CI commands

Potential scripts:
```text
composer test:browser
composer test:e2e
npm run playwright:install
```

Do not install or pin these dependencies until the implementation milestone that
introduces browser tests.

## Test environment matrix

### Fast local/CI browser smoke
Uses fake or mocked media where possible.

Services:
```text
Laravel app
Vite dev server or built assets
PostgreSQL
Redis
Reverb when realtime behavior is tested
```

Purpose:
- route/page loading;
- Inertia navigation;
- no JavaScript errors;
- no console errors where feasible;
- UI state transitions;
- form behavior;
- auth redirects.

### Full local LiveKit E2E
Uses real local LiveKit.

Services:
```text
Laravel app
Vite dev server or built assets
PostgreSQL
Redis
Reverb
LiveKit
```

WSL2 note:
- local runs may need `LIVEKIT_RTC_UDP_PORT_RANGE=50000-50020`;
- production-like verification should still use `50000-60000/UDP`.

Purpose:
- real LiveKit join;
- multi-browser participant visibility;
- microphone/camera state;
- screen share publication/subscription;
- screen share layout enter/exit;
- Reverb + LiveKit interaction.

### Production smoke
Manual or scheduled against a staging deployment, not developer production data.

Purpose:
- real HTTPS/WSS routing;
- Dokploy Traefik routing;
- LiveKit signaling via public domain;
- media ports/firewall;
- cross-browser sanity.

## Test data principles
- Tests create their own users, rooms and memberships.
- Tests must not depend on seeded local accounts.
- Browser tests should prefer factories and Laravel test helpers where possible.
- E2E tests must not run destructive operations against unknown production data.
- Use explicit room names in tests, e.g. `E2E Daily Call`.
- Use unique public room data per test to avoid cross-test collisions.

## Milestone E0 — Browser test foundation `[x]`
Install and configure browser testing infrastructure without testing real media.

Tasks:
- [x] add Pest Browser plugin
- [x] add Playwright dependency and browser install command
- [x] create `tests/Browser`
- [x] configure browser timeout for local WSL2
- [x] configure screenshots/artifacts output
- [x] add `.gitignore` entries for browser artifacts
- [x] add browser test scripts
- [x] add documentation for local Sail execution
- [x] verify browser test can open `/`

Implementation notes:
- Browser tests use built Vite assets from `public/build`.
- In `APP_ENV=testing`, Laravel ignores the local `public/hot` file so stale or
  unavailable Vite dev servers do not create blank-page browser failures.

Acceptance:
- [x] `visit('/')` browser test passes
- [x] browser test can assert no JavaScript errors
- [x] browser test artifacts are ignored by Git
- [x] local command works through Sail
- [x] ordinary Pest suite still passes
- [x] frontend typecheck/lint/build still pass

Suggested verification:
```text
./vendor/bin/sail artisan test --compact
./vendor/bin/sail npm run types:check
./vendor/bin/sail npm run lint:check
./vendor/bin/sail npm run build
./vendor/bin/sail composer test:browser
```

## Milestone E1 — Auth and room navigation smoke `[x]`
Validate the browser-visible room workflow before media.

Scenarios:
- [x] unauthenticated user visiting `/rooms/{room}/prepare` is redirected to
      login
- [x] authenticated user can open `/rooms`
- [x] authenticated user can create a room from the UI
- [x] room creation redirects to `/rooms/{room}/prepare`
- [x] rooms list links to preparation page, not directly to call
- [x] prepare page renders room name and device selectors
- [x] user can continue without microphone and without camera
- [x] call page opens without JavaScript errors

Acceptance:
- [x] browser smoke covers `/rooms`, `/prepare`, `/call`
- [x] no JavaScript errors on covered pages
- [x] no app sidebar appears on prepare/waiting/call pages
- [x] backend feature tests remain green

## Milestone E2 — Device selection and media permission UX `[x]`
Validate preparation-room behavior with browser media permissions.

Scenarios:
- [x] prepare page works when no camera is selected
- [x] prepare page works when no microphone is selected
- [x] clicking refresh/request devices updates microphone select
- [x] clicking refresh/request devices updates camera select
- [x] after camera permission, first available camera is selected automatically
- [x] local preview starts when a camera is selected
- [x] local preview can stop
- [x] permission/device errors are shown without crashing the page

Acceptance:
- [x] tests cover no-device and fake-device cases
- [x] selected camera is preserved when navigating prepare -> waiting/call
- [x] selected microphone is preserved when navigating prepare -> waiting/call
- [x] no JavaScript errors during permission flow

Implementation notes:
- Prefer browser fake media devices for deterministic CI.
- Use real devices only for manual local smoke.
- Avoid requiring physical camera/microphone in CI.
- The automated fake-device path uses `_e2e_media=fake|error`, but Laravel only
  exposes this mode while `APP_ENV=testing`.

## Milestone E3 — Single-user LiveKit call smoke `[x]`
Validate that one browser can enter the LiveKit room and control local media.

Scenarios:
- [x] user joins call with no microphone/camera selected
- [x] participant tile appears for local user
- [x] microphone button toggles local microphone state
- [x] camera button toggles local camera state
- [x] screen share button appears only when `can_publish_screen` is true
- [x] screen share button is hidden when `can_publish_screen` is false
- [x] disconnect returns user to preparation page

Acceptance:
- [x] real LiveKit token is issued
- [x] browser connects to local LiveKit
- [x] UI reflects connected state
- [x] UI reflects mic/camera/screen-share button state
- [x] no JavaScript errors during join/disconnect

Implementation notes:
- E3 uses local LiveKit signaling and real backend-issued LiveKit tokens.
- Browser tests run inside the Sail container. In that environment LiveKit must
  advertise the Docker network gateway as `rtc.node_ip`; otherwise Chromium sees
  ICE candidates for `127.0.0.1` and stays in `connecting`.
- Before running E3 locally, recreate LiveKit with the Sail network gateway:

```bash
LIVEKIT_NODE_IP="$(docker network inspect systemcall-app_sail --format '{{range .IPAM.Config}}{{.Gateway}}{{end}}')" \
docker compose -f compose.sail.yaml -f compose.sail-livekit.yaml up -d --force-recreate livekit
```

- The local fake media path still uses `_e2e_media=fake`, but only for
  deterministic microphone/camera UI toggles in headless Chromium. LiveKit
  signaling, token validation and room connection remain real.

## Milestone E4 — Multi-user room presence `[ ]`
Validate two independent browser contexts in the same room.

Scenarios:
- [ ] user A joins room
- [ ] user B joins same room
- [ ] user A sees user B tile
- [ ] user B sees user A tile
- [ ] user A disconnecting removes or updates their presence for user B
- [ ] reconnecting/reloading does not duplicate participant tiles

Acceptance:
- [ ] two browser contexts can authenticate as different users
- [ ] both contexts join the same LiveKit room
- [ ] participant grid reaches expected tile count
- [ ] no duplicate tile after refresh/rejoin
- [ ] no JavaScript errors in either browser context

## Milestone E5 — Audio/video controls `[ ]`
Validate user-visible audio/video behavior in multi-user calls.

Scenarios:
- [ ] user A toggles microphone on/off
- [ ] user B sees user A microphone state change
- [ ] user A toggles camera on/off
- [ ] user B sees user A camera state change
- [ ] camera-off state shows fallback avatar
- [ ] camera-on state shows a video element
- [ ] audio/video toggles remain usable after reconnect or reload

Acceptance:
- [ ] microphone UI state propagates between browsers
- [ ] camera UI state propagates between browsers
- [ ] fallback avatar only appears when camera is off and no screen-share video
      is active
- [ ] no JavaScript errors in either browser context

## Milestone E6 — Screen sharing `[ ]`
Validate Zoom-like screen-share behavior.

Scenarios:
- [ ] user A with `can_publish_screen=true` sees screen-share button
- [ ] user A starts screen share
- [ ] user A sees screen-share layout
- [ ] user B sees screen-share layout
- [ ] large screen-share tile occupies the main area
- [ ] participant list moves to vertical scrollable side rail
- [ ] screen-share tile uses screen video, not camera video
- [ ] screen-share badge appears only while sharing is active
- [ ] user A stops screen share
- [ ] user A returns to normal participant grid
- [ ] user B returns to normal participant grid
- [ ] stale black screen-share tile does not remain after stop

Acceptance:
- [ ] screen-share start is visible in both browser contexts
- [ ] screen-share stop is visible in both browser contexts
- [ ] remote browser does not keep stale `screenShare` state
- [ ] screen-share media element is removed after stop
- [ ] layout does not stay in share mode after stop
- [ ] no JavaScript errors in either browser context

## Milestone E7 — Raised hand `[ ]`
Validate application realtime state through Reverb.

Scenarios:
- [ ] user A raises hand
- [ ] user A sees hand button active
- [ ] user B sees raised-hand count update
- [ ] user B sees user A hand state in participant/application UI
- [ ] user A lowers hand
- [ ] user B sees raised-hand count return to previous value
- [ ] repeated clicks do not duplicate or corrupt hand state

Acceptance:
- [ ] hand state persists through backend endpoint
- [ ] hand state propagates through Reverb
- [ ] UI state is consistent after reload
- [ ] no JavaScript errors in either browser context

## Milestone E8 — Waiting room and moderation smoke `[ ]`
Validate room admission workflows that affect call access.

Scenarios:
- [ ] waiting-room enabled room sends participant to waiting page
- [ ] waiting page shows selected microphone/camera labels
- [ ] waiting page shows camera preview when camera was selected
- [ ] moderator sees pending participant
- [ ] moderator approves participant
- [ ] participant transitions to call page
- [ ] moderator rejects participant
- [ ] participant sees rejected state and cannot join

Acceptance:
- [ ] waiting-room approval is required before LiveKit token is used
- [ ] waiting room events propagate through Reverb
- [ ] approved participant can join LiveKit
- [ ] rejected participant cannot join LiveKit

## Milestone E9 — Chat smoke `[ ]`
Validate the side chat used during calls.

Scenarios:
- [ ] user A opens chat sidebar
- [ ] user A sends text message
- [ ] user B receives message without page refresh
- [ ] empty messages cannot be sent
- [ ] chat can close and reopen without losing recent messages

Acceptance:
- [ ] chat message persists through backend endpoint
- [ ] chat event propagates through Reverb
- [ ] message appears in both browser contexts
- [ ] no JavaScript errors during send/receive

## Milestone E10 — CI integration `[ ]`
Add browser tests to automated pipelines with clear cost boundaries.

Recommended CI tiers:
- always: PHP unit/feature tests, Pint, PHPStan, frontend typecheck, lint, build
- always or PR label: browser smoke without real LiveKit media
- scheduled/manual: full LiveKit E2E with Reverb and browser media

Tasks:
- [ ] add browser smoke workflow
- [ ] install Playwright browsers in CI
- [ ] cache npm/composer dependencies
- [ ] upload screenshots/videos/traces on failure
- [ ] add optional manual LiveKit E2E workflow
- [ ] document how to reproduce CI browser failures locally

Acceptance:
- [ ] CI browser smoke is deterministic
- [ ] failures include useful artifacts
- [ ] full LiveKit E2E is available but not forced on every small PR unless the
      team decides otherwise

## Critical assertions checklist

Rooms:
- [ ] guest cannot open protected call-flow pages
- [ ] authenticated user can create/open room
- [ ] public/private/password room access behaves correctly
- [ ] prepare/waiting/call pages use fullscreen call layout

Audio:
- [ ] no microphone is valid
- [ ] selected microphone is preserved
- [ ] microphone toggle works
- [ ] remote participant sees microphone state

Video:
- [ ] no camera is valid
- [ ] selected camera is preserved
- [ ] first camera auto-selects after permission on prepare page
- [ ] camera preview starts/stops
- [ ] camera toggle works
- [ ] remote participant sees camera state

Screen sharing:
- [ ] button is permission-gated by `can_publish_screen`
- [ ] start share switches both browsers to share layout
- [ ] stop share returns both browsers to normal grid
- [ ] no stale black share tile remains
- [ ] large share tile shows screen track with `object-contain`
- [ ] participant side rail scrolls

Raised hand:
- [ ] raise hand updates local UI
- [ ] raise hand propagates to remote UI
- [ ] lower hand propagates to remote UI
- [ ] repeated toggles remain idempotent

Realtime:
- [ ] Reverb connects
- [ ] room channel authorization works
- [ ] chat, hand and waiting-room events propagate
- [ ] LiveKit media state is not duplicated through Reverb

## Manual smoke checklist before full automation
Until browser E2E is implemented, manually verify after major media changes:

1. Start local Sail stack.
2. Start local LiveKit/Reverb as documented for local calls.
3. Open room as user A in browser A.
4. Open same room as user B in browser B/private window.
5. Join without microphone and camera.
6. Toggle microphone.
7. Toggle camera.
8. Send chat message.
9. Raise and lower hand.
10. Start screen share.
11. Confirm both browsers enter screen-share layout.
12. Stop screen share.
13. Confirm both browsers return to normal grid.
14. Confirm no stale black share tile remains.
