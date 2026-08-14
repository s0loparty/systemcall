<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application running on PHP 8.3. You are an expert with the Laravel ecosystem. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists (settled decisions, non-obvious traps, standing constraints). Framework and package guidelines that only apply to specific paths (testing, frontend, components) also live there, under `.ai/rules/boost` — this is not just recorded decisions, it is load-bearing guidance you have not seen inline. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.
- Record durable rules with `record-rule` so the next agent or teammate inherits them instead of working them out again. Pass a `glob` (e.g. `app/Http/Controllers/**`), a short `title`, and a few-line `note`. Always use `record-rule`, never your native memory or notes tool — native memory is personal and session-scoped; only `.ai/rules` is shared with the team and persists in the repo.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== inertia-laravel/core rules ===

# Inertia

- Inertia creates fully client-side rendered SPAs without modern SPA complexity, leveraging existing server-side patterns.
- Components live in `resources/js/pages` (unless specified in `vite.config.js`). Use `Inertia::render()` for server-side routing instead of Blade views.
- ALWAYS use `search-docs` tool for version-specific Inertia documentation and updated code examples.
- IMPORTANT: Activate `inertia-vue-development` when working with Inertia Vue client-side patterns.

# Inertia v3

- Use all Inertia features from v1, v2, and v3. Check the documentation before making changes to ensure the correct approach.
- New v3 features: standalone HTTP requests (`useHttp` hook), optimistic updates with automatic rollback, layout props (`useLayoutProps` hook), instant visits, simplified SSR via `@inertiajs/vite` plugin, custom exception handling for error pages.
- Carried over from v2: deferred props, infinite scroll, merging props, polling, prefetching, once props, flash data.
- When using deferred props, add an empty state with a pulsing or animated skeleton.
- Axios has been removed. Use the built-in XHR client with interceptors, or install Axios separately if needed.
- `Inertia::lazy()` / `LazyProp` has been removed. Use `Inertia::optional()` instead.
- Prop types (`Inertia::optional()`, `Inertia::defer()`, `Inertia::merge()`) work inside nested arrays with dot-notation paths.
- SSR works automatically in Vite dev mode with `@inertiajs/vite` - no separate Node.js server needed during development.
- Event renames: `invalid` is now `httpException`, `exception` is now `networkError`.
- `router.cancel()` replaced by `router.cancelAll()`.
- The `future` configuration namespace has been removed - all v2 future options are now always enabled.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== laravel-octane/core rules ===

# Laravel Octane

This application uses Laravel Octane, a long-running PHP server. The application bootstraps once and handles many requests within the same process.

- Never store request-specific state in singletons or static properties, because it can leak across requests.
- Use `config('octane.server')` to detect the active driver (`swoole`, `roadrunner`, or `frankenphp`).
- Prefer scoped bindings (`$this->app->scoped()`) over singletons for per-request services.

When working on Octane-specific features (concurrency, shared tables, memory, driver configuration, testing), invoke `octane-development` for detailed rules.

=== wayfinder/core rules ===

# Laravel Wayfinder

Use Wayfinder to generate TypeScript functions for Laravel routes. Import from `@/actions/` (controllers) or `@/routes/` (named routes).

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- The `{name}` argument should not include the test suite directory. Use `php artisan make:test --pest SomeFeatureTest` instead of `php artisan make:test --pest Feature/SomeFeatureTest`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

=== inertia-vue/core rules ===

# Inertia + Vue

Vue components must have a single root element.
- IMPORTANT: Activate `inertia-vue-development` when working with Inertia Vue client-side patterns.

</laravel-boost-guidelines>

# Video Calls — Project-Specific Rules

These rules extend the Laravel Boost guidelines above.

If a project-specific rule conflicts with a generic recommendation,
these project-specific architectural decisions take precedence.

Do not override version-specific Laravel/package guidance.
Always verify installed package versions before using their APIs.

## Project Goal

Build a production-oriented group audio/video calling service.

Target stack:

- Laravel
- Vue 3
- TypeScript
- TailwindCSS
- PostgreSQL
- Redis
- Laravel Reverb
- LiveKit
- Docker Compose
- Dokploy
- FrankenPHP + Laravel Octane

Do not assume Laravel, PHP, Vue, LiveKit, or package versions.
Inspect the repository and installed dependencies first.

## Project Documentation

Before implementing video-call functionality, read:

- `docs/video-calls/IMPLEMENTATION_PLAN.md`
- `docs/video-calls/ARCHITECTURE.md`
- `docs/video-calls/LIVEKIT.md`
- `docs/video-calls/DEPLOYMENT.md`
- `docs/video-calls/QUALITY_CHECKLIST.md`

The implementation plan defines the current milestone.

Work one milestone at a time.

Do not start the next milestone until the current milestone's
acceptance criteria are satisfied.

Update `IMPLEMENTATION_PLAN.md` after completing a milestone.

## Production Target

Initial production environment:

ONE VPS managed by Dokploy.

Dokploy owns:

- Docker deployment/orchestration
- host-level Traefik
- HTTP/HTTPS routing
- TLS certificates
- public ports 80 and 443

The application Docker Compose MUST NOT contain its own Traefik service.

## Production Services

The target Compose services are:

- `app`
- `worker`
- `scheduler`
- `reverb`
- `postgres`
- `redis`
- `livekit`

`app`, `worker`, `scheduler`, and `reverb`
should use the same Laravel application image with different commands.

Do not introduce additional infrastructure services unless required
by the current milestone or approved by the user.

## Dokploy Routing

Expected production routing:

call.example.com
    -> app:<internal application HTTP port>

ws.call.example.com
    -> reverb:8080

livekit.example.com
    -> livekit:7880

These ports are container/internal ports.

Do not publish application HTTP ports to the host solely for routing.
Dokploy-managed Traefik handles routing.

## LiveKit Networking

LiveKit signaling:

Browser
    -> HTTPS/WSS
    -> Dokploy Traefik
    -> livekit:7880

LiveKit WebRTC media bypasses the HTTP reverse proxy.

Expected directly published media ports:

- 7881/TCP
- 50000-60000/UDP
- 3478/UDP when TURN/UDP is enabled

Dokploy owns host 443/TCP.

LiveKit TURN/TLS MUST NOT bind host 443 in the initial
single-VPS architecture.

TURN/TLS on another port such as 5349 may only be introduced after
checking the documentation for the installed/pinned LiveKit version.

## Architecture Boundaries

### Laravel

Laravel is authoritative for:

- users
- rooms
- room settings
- room members
- roles
- permissions
- guest authorization
- waiting room
- bans
- moderation
- LiveKit token issuance
- chat persistence
- raised hands application state
- global pin
- subrooms
- scheduled cleanup
- business events

### LiveKit

LiveKit is authoritative for:

- microphone media
- camera media
- screen sharing
- WebRTC
- SFU routing
- media tracks
- realtime media presence
- active speaker
- media subscriptions
- adaptive media

Never transport audio/video through Laravel or Reverb.

Do not duplicate ordinary LiveKit media state through Reverb.

### Laravel Reverb

Reverb is used for application realtime events such as:

- waiting-room updates
- role changes
- moderation notifications
- chat
- raised hands
- global pin
- subroom changes
- room deletion

## LiveKit Authorization

Never expose `LIVEKIT_API_SECRET` to frontend code.

All trusted LiveKit server operations must be encapsulated behind
a dedicated service layer.

Before issuing a LiveKit token validate:

1. room exists;
2. room is active;
3. participant identity is valid;
4. room password when required;
5. participant is not banned;
6. waiting-room approval when enabled;
7. participant permissions;
8. requested main room/subroom is authorized.

LiveKit participant identity must be generated by the backend and
must map to a backend RoomMember.

Never trust a participant identity supplied directly by the browser.

## Guests

Guest identity must be server-verifiable.

Use an approach such as:

- signed/encrypted cookie;
- server-side session;
- signed temporary guest token.

Do not trust an arbitrary browser-provided `guest_id`.

## Roles

Supported room roles:

- owner
- moderator
- participant

All privileged operations require backend authorization.

A moderator may force-mute another participant when supported.

Never silently enable another participant's microphone.

## Production Laravel Runtime

Production Laravel runs using FrankenPHP + Laravel Octane.

Do not use:

`php artisan serve`

for production.

Production images must contain application code and built frontend
assets.

Do not production-bind-mount the repository source code.

## Dependencies

Follow the Laravel Boost rule requiring approval before changing
dependencies, with this project-specific exception:

Dependencies explicitly required by the currently approved milestone
in `IMPLEMENTATION_PLAN.md` may be installed or updated when necessary.

Before installing anything:

1. inspect existing dependencies;
2. search version-specific documentation;
3. prefer existing packages when suitable;
4. avoid unrelated dependency upgrades.

## Redis

Initially one Redis instance may serve:

- Laravel cache
- Laravel sessions
- Laravel queues
- Reverb
- LiveKit

Do not hardcode assumptions preventing Redis from being split later.

## Testing

Follow the Laravel Boost testing rules.

Every behavioral change requires an appropriate automated test.

LiveKit server integration should be behind a service abstraction
that can be faked/mocked in ordinary application tests.

Real media sessions are not required for every backend test.

## Verification

Follow the verification commands defined by Laravel Boost
and the actual repository.

Additionally, after Docker changes run:

`docker compose config`

Run frontend typecheck/lint/build commands when they exist.

Do not invent commands that are not configured in the repository.

## Security

Always consider:

- validation
- Policies/Gates
- rate limiting
- password hashing
- CSRF
- secure cookies
- XSS-safe chat rendering
- unpredictable public room IDs
- environment-only secrets
- LiveKit webhook verification
- idempotent webhook processing

Never trust frontend claims about:

- role
- ownership
- participant identity
- guest identity
- waiting-room approval

## Git Safety

Before significant work inspect:

- `git status`
- `git diff`

Do not:

- push unless explicitly requested;
- rewrite Git history;
- reset unrelated changes;
- delete user work;
- mass-format unrelated files.

## Milestone Completion

A milestone is complete only when:

- implementation is complete;
- acceptance criteria are satisfied;
- required tests pass;
- relevant verification passes;
- documentation reflects the implementation;
- `IMPLEMENTATION_PLAN.md` is updated.

If infrastructure or credentials prevent verification,
document the exact blocker instead of pretending the check passed.

# Frontend Architecture — Inertia + Vue 3

These rules apply to all frontend work in this project and extend the existing
Laravel Boost / Inertia / Vue guidelines.

Frontend code must be treated as production application code, not as disposable
UI glue. The same standards applied to backend architecture must also be
applied to frontend architecture.

Before creating or modifying frontend code:

1. inspect existing frontend structure;
2. inspect sibling components;
3. inspect existing composables;
4. inspect existing stores;
5. inspect existing types;
6. inspect existing reusable UI components;
7. inspect established naming conventions;
8. use the project's Laravel Boost / Inertia documentation tools before relying
   on framework APIs.

Do not introduce a new frontend architectural pattern when an existing project
pattern already solves the problem.

## Separation of Responsibilities

Separate:

- page orchestration;
- presentation;
- domain/business logic;
- server communication;
- realtime communication;
- media logic;
- reusable state;
- local UI state.

Do not mix all of these responsibilities inside one Vue component. A Vue
component should have a clear reason to change.

## Inertia Pages

Files under `resources/js/pages` are route-level views.

Pages should primarily:

- receive Inertia props;
- compose feature components;
- initialize page-level composables;
- connect page sections;
- provide high-level layout;
- handle route/page-specific orchestration.

Pages should not contain large amounts of:

- business logic;
- LiveKit implementation details;
- Reverb implementation details;
- fetch/request implementation;
- complex state machines;
- media-device logic;
- large transformation algorithms;
- reusable domain logic.

A page should normally be understandable as composition/orchestration. Avoid
turning pages into god-components. If a page grows substantially, identify
independent features/sections and extract them.

## Presentational Components

Presentation components should focus on rendering UI. Prefer components that
receive typed props and emit meaningful events.

Examples:

```text
ParticipantTile.vue
ParticipantGrid.vue
CallControls.vue
MicrophoneButton.vue
CameraButton.vue
ChatMessage.vue
WaitingParticipantRow.vue
```

Presentation components should not directly know about application
infrastructure unless their responsibility explicitly requires it. For example,
`MicrophoneButton.vue` should ideally not create a LiveKit Room instance or call
backend APIs directly. It should receive state and emit user intent.

## Smart vs Presentational Components

Prefer a clear distinction between feature/container components and
presentational components.

Feature/container components may:

- connect composables;
- read application state;
- coordinate feature behavior;
- handle domain-specific actions.

Presentational components should mostly:

- receive props;
- render state;
- emit user intentions.

Do not force every component into a rigid pattern, but maintain clear
boundaries.

## No God Components

Do not create Vue components that simultaneously:

- connect to LiveKit;
- subscribe to Reverb;
- fetch backend data;
- manage permissions;
- manage devices;
- render participant grid;
- render chat;
- render waiting room;
- implement moderation;
- manage dialogs;
- handle routing.

Warning signs include:

- very large `<script setup>`;
- dozens of refs/computed values;
- many unrelated watchers;
- many unrelated event handlers;
- several independent feature areas;
- complex lifecycle hooks;
- direct usage of multiple infrastructure SDKs.

Do not split purely by line count. Split by responsibility and domain
boundaries.

## Composables

Use composables for reusable behavior and feature logic.

Examples:

```text
useLiveKit
useCall
useParticipants
useMediaDevices
useScreenShare
useRoom
useRoomPermissions
useRoomChat
useWaitingRoom
useModeration
useSubrooms
```

Each composable must have a focused responsibility. Do not create a single
`useVideoCallEverything()` that owns all application logic. Composables should
expose a small, intentional public API and keep internal implementation details
private.

Example boundaries:

- `useLiveKit`: LiveKit Room lifecycle, connect, disconnect, LiveKit events and
  media connection state. It is not responsible for Laravel room authorization,
  chat persistence, waiting-room business rules or route navigation.
- `useMediaDevices`: available cameras, microphones, supported output devices,
  selected devices, browser media permissions and device changes.
- `useRoomPermissions`: client-side interpretation of application permissions.
  Backend authorization remains authoritative.
- `useRoomChat`: loading/sending room messages, chat application events and chat
  feature state. It must not own LiveKit media.

## Business Logic

Frontend business/domain rules must not be hidden inside template conditions.
Avoid scattering rules such as:

```vue
v-if="user.id === room.ownerId || member.role === 'moderator' && ..."
```

Instead expose meaningful derived state:

```ts
const canKickParticipant = computed(...)
const canManageWaitingRoom = computed(...)
```

Frontend permission checks improve UX only. Backend authorization remains
mandatory and authoritative.

## Server Communication

Do not scatter raw network requests throughout arbitrary Vue components. Use the
project's established Inertia patterns.

Where standalone HTTP requests are appropriate, encapsulate them in:

- feature composables;
- dedicated action/service modules;
- existing project abstractions.

Follow the installed Inertia version's supported APIs. Do not assume Axios
exists. Do not introduce Axios unless required and approved.

Avoid duplicating endpoint paths as raw strings throughout the application. Use
Laravel Wayfinder / generated route actions when available and consistent with
the project.

For API routes intended for mobile or third-party clients, use Laravel Sanctum
as the authentication mechanism for `api.php` routes and protect them with the
appropriate Sanctum middleware/guards.

## Inertia Responsibilities

Use Inertia for application/page communication where it makes sense. Do not
treat Inertia props as a global mutable application store. Inertia props
represent server-provided page state.

For highly realtime state such as call participants/media tracks, use the
appropriate realtime system rather than forcing updates through Inertia
navigation.

Use:

- Laravel/Inertia for page/business state;
- Reverb for application realtime events;
- LiveKit for media realtime state.

## State Ownership

Every piece of state should have a clear owner.

Use component-local state when state belongs only to one component/feature
instance. Examples:

- dialog open state;
- local menu state;
- hovered participant;
- personal pin.

Use composable state when behavior/state belongs to a reusable feature.

Use Pinia only for genuinely shared application state spanning multiple
unrelated component branches or routes/features. Do not use Pinia automatically
for every piece of state. Do not copy all Inertia props or all LiveKit
participant/track state into Pinia.

## LiveKit State

LiveKit SDK is the source of truth for realtime media state. Do not manually
recreate LiveKit's internal participant/track state in a parallel store. Derive
UI state from LiveKit APIs/events.

LiveKit owns:

- connected participants;
- tracks;
- microphone state;
- camera state;
- screen share;
- active speaker;
- connection quality.

Laravel/application owns:

- participant role;
- ban state;
- waiting-room approval;
- global pin;
- moderation permissions.

## Reverb State

Reverb is a transport for application events. Do not create a second copy of
business state solely because an event arrived through Reverb.

When appropriate:

1. receive event;
2. update the correct feature state;
3. invalidate/reload server state when necessary.

Manage subscriptions carefully. Always unsubscribe/cleanup listeners when
component/composable lifecycle ends. Avoid duplicate subscriptions after
navigation/reconnect.

## Feature-Based Organization

Prefer organizing complex frontend code by feature/domain rather than placing
everything in generic folders.

For the video-call feature, an acceptable structure may look like:

```text
resources/js/
├── pages/
│   └── calls/
│       └── Show.vue
├── features/
│   └── calls/
│       ├── components/
│       │   ├── ParticipantGrid.vue
│       │   ├── ParticipantTile.vue
│       │   ├── CallControls.vue
│       │   ├── ParticipantsSidebar.vue
│       │   ├── ChatSidebar.vue
│       │   └── WaitingRoomPanel.vue
│       ├── composables/
│       │   ├── useCall.ts
│       │   ├── useLiveKit.ts
│       │   ├── useMediaDevices.ts
│       │   ├── useRoomChat.ts
│       │   ├── useWaitingRoom.ts
│       │   └── useModeration.ts
│       ├── types/
│       └── utils/
├── components/
└── layouts/
```

Do not blindly create this exact structure if the repository already follows
another established convention. First inspect the existing project. Follow the
existing structure unless there is a strong reason to evolve it.

## View vs Business Logic

Vue templates should remain declarative. Complex calculations,
transformations and domain rules belong in script/composables, not inline
templates.

Prefer:

```ts
const canModerateParticipant = computed(() => ...)
```

and:

```vue
<button v-if="canModerateParticipant">
```

over large inline boolean expressions in templates.

## TypeScript

Do not use `any` unless there is a documented unavoidable reason.

Prefer:

- explicit interfaces/types;
- discriminated unions;
- typed emits;
- typed props;
- typed API payloads;
- typed composable return values where useful.

Reuse types generated from backend where the project already provides that
capability. Do not manually duplicate backend enums/types if the project already
has a reliable generation mechanism. Avoid unsafe casts used merely to silence
TypeScript.

## Props and Emits

Keep component APIs intentional. Avoid passing enormous domain objects when the
component only needs a few values, unless passing the domain object itself is
the established project pattern and improves maintainability.

Do not use excessive prop drilling when shared feature state has a more
appropriate owner. At the same time, do not introduce Pinia merely to avoid
passing two props.

Emit semantic user intentions. Prefer:

```text
mute
unmute
kick
approve
reject
select
leave
```

rather than tightly coupling children to parent implementation details. Type all
emits.

## Events, Watchers and Lifecycle

Use watchers carefully. Do not use watchers as a substitute for clear data
flow. Avoid chains of watchers that indirectly mutate each other. Prefer
computed values, explicit actions and lifecycle-aware composables.

Every watcher/subscription/listener that needs cleanup must have cleanup.

When a page/component is destroyed or navigated away from:

- remove event listeners;
- unsubscribe Reverb channels when appropriate;
- remove browser event listeners;
- stop temporary media tracks when owned by that feature;
- disconnect LiveKit when the call session ends;
- clear timers;
- cancel pending feature work when possible.

Do not leak listeners after repeated Inertia navigation.

## Async State and Error Handling

For asynchronous operations expose clear states when needed:

```text
idle
loading
success
error
```

For call connectivity:

```text
connecting
connected
reconnecting
disconnected
kicked
banned
room-ended
```

Do not reduce every async operation to a single global `loading` boolean.
Prevent accidental duplicate requests/actions where necessary.

Do not silently swallow errors. Handle expected errors near the feature that can
meaningfully present them, such as microphone permission denied, camera
unavailable, room password invalid, waiting-room rejection, room ended,
connection failed or moderation action failure.

Infrastructure details should not be dumped directly to users. Unexpected
errors should still be observable/loggable.

## TailwindCSS and Accessibility

Use existing design-system/UI conventions. Reuse existing UI primitives and
components before inventing new ones.

Do not create enormous unreadable class lists when a reusable UI component or
existing abstraction already solves the problem. Avoid extracting trivial
one-use wrappers solely to reduce Tailwind line length. Do not introduce custom
CSS unnecessarily when Tailwind utilities cleanly solve the problem.

Interactive components should support appropriate:

- semantic HTML;
- buttons for actions;
- labels;
- keyboard interaction;
- focus management;
- disabled states;
- ARIA attributes when required.

Do not implement clickable `<div>` elements when a semantic button/link is
appropriate. Dialogs and menus should follow existing accessible project
components/primitives.

## Performance

Avoid premature optimization, but design the call UI with realtime performance
in mind.

Do not:

- deeply watch huge participant collections unnecessarily;
- recreate expensive objects on every render;
- render invisible participant video at full quality;
- subscribe every client to all streams at maximum resolution;
- perform expensive transforms directly in templates.

For many participants, prioritize visible tiles, active speaker, pinned
participant and screen share. Use LiveKit adaptive media capabilities
appropriately.

## Component Extraction and Reusability

Extract a component/composable when doing so creates a meaningful responsibility
boundary. Do not fragment a simple component into dozens of tiny files merely to
satisfy line-count targets. There is no strict maximum number of lines.
Architecture is based on cohesion and responsibilities, not arbitrary file
size.

Do not make everything generic prematurely. First identify whether something is:

- application-specific;
- call-feature-specific;
- genuinely reusable globally.

For example, `ParticipantTile.vue` belongs to the call domain. A generic
`BaseButton.vue` may belong to shared UI if the project already uses such a
design system. Avoid creating generic abstractions before multiple real use
cases exist.

## Testing Frontend Logic

Frontend business-critical behavior should be testable. When frontend tests
exist, cover important behavior such as:

- permission-derived UI;
- call state transitions;
- waiting-room behavior;
- moderation UI;
- reconnect handling;
- device errors.

Keep business logic outside templates so it can be tested more easily. Backend
authorization tests remain mandatory even if frontend permission UI is tested.

## Frontend Code Review Checklist

Before considering frontend work complete, inspect:

- Did the page become a god-component?
- Is business logic buried in the template?
- Are network requests scattered through UI components?
- Does any component directly combine Laravel API, Reverb and LiveKit without a
  clear orchestration responsibility?
- Is shared state stored in the correct place?
- Is LiveKit state unnecessarily duplicated?
- Are subscriptions/listeners cleaned up?
- Are props/emits typed?
- Is `any` being used unnecessarily?
- Are backend permission checks still present?
- Are error/loading/reconnect states represented?
- Are existing UI components reused?
- Is the resulting structure easier to maintain than before?

If not, refactor before marking the task complete.

## Video Call UI Specific Rules

The call page must be composed from focused feature components.

A target conceptual structure:

```text
CallPage
 |
 +-- CallLayout
      |
      +-- ParticipantGrid
      |    +-- ParticipantTile
      |
      +-- ScreenShareView
      |
      +-- CallControls
      |
      +-- ParticipantsSidebar
      |
      +-- ChatSidebar
      |
      +-- WaitingRoomPanel
```

Feature logic should be separated conceptually:

```text
useCall
 |
 +-- useLiveKit
 +-- useMediaDevices
 +-- useRoomPermissions
 +-- useRoomChat
 +-- useWaitingRoom
 +-- useModeration
```

This diagram is guidance, not a requirement to create exactly these files.
Preserve existing project conventions.

## Final Frontend Rule

Frontend architecture must receive the same level of care as backend
architecture.

Do not choose the fastest implementation if it creates:

- tightly coupled Vue components;
- duplicated state;
- infrastructure logic embedded in presentation components;
- untestable business rules;
- god-components;
- hidden side effects;
- unmanaged realtime subscriptions.

Prefer small, cohesive architectural steps that keep the application
maintainable as the call feature grows.
