# Systemcall Design System — Implementation Plan

> Goal: обновить UI всего приложения до цельного SaaS-продукта для групповых аудио/видеозвонков.
> References: светлый clean SaaS, мягкие фиолетово-синие акценты, карточки с тонкими бордерами, крупная типографика, аккуратные состояния устройств и комнат.

## Hard Rules

- Work milestone by milestone. Do not redesign unrelated pages before the current milestone is complete.
- Prefer existing shadcn-vue components from `resources/js/components/ui`.
- If a needed shadcn component is missing, install it before writing custom primitives.
- Do not leave debug/system copy in UI: no raw IDs, LiveKit/Reverb internals, status dumps, local-only wording, stack/runtime messages or test hints.
- Product copy must read like a real SaaS product with minimal functionality, not like internal engineering tooling.
- Keep frontend architecture rules: pages orchestrate, feature components render, composables own behavior.
- Keep all current call features working: rooms, guest join, device selection, waiting room, call UI, chat, raised hands, pinning, moderation and screen share.
- Use Russian UI copy consistently.
- Maintain mobile layouts for every changed page.
- Every changed behavior needs tests; visual-only changes need at least format/type/lint/build and existing browser smoke where relevant.

## Current UI Inventory

Existing shadcn-vue primitives:

- `alert`
- `avatar`
- `badge`
- `breadcrumb`
- `button`
- `card`
- `checkbox`
- `collapsible`
- `dialog`
- `dropdown-menu`
- `input`
- `input-otp`
- `label`
- `navigation-menu`
- `select`
- `separator`
- `sheet`
- `sidebar`
- `skeleton`
- `sonner`
- `spinner`
- `tooltip`

Likely missing shadcn components to add when needed:

- `switch` for waiting-room toggles and settings.
- `tabs` for auth/settings/account sections if current layout needs better grouping.
- `textarea` if room description remains editable as a component.
- `popover` for compact filters and room actions.
- `command` only if room search becomes rich/autocomplete.

## Design Direction

Visual language:

- Light-first SaaS UI.
- Background: soft off-white with subtle radial purple/blue haze, not flat white.
- Primary accent: violet/blue gradient for main CTAs.
- Success/accent: emerald for live/ready/device states.
- Warning: amber for waiting-room states.
- Danger: red only for destructive moderation and leave call.
- Radius: mostly `rounded-md` for product surfaces, larger radius only for avatar/icon tiles when intentional.
- Borders: thin neutral borders, low-opacity shadows, no heavy black panels outside actual video/call surfaces.

Typography:

- Keep current `Instrument Sans` unless a typography milestone explicitly changes it.
- Large page titles, concise descriptions, medium-weight section headers.
- Avoid technical captions such as `LiveKit room`, `Reverb connected`, raw public IDs or debug counters in visible UI.

Motion:

- Subtle hover lift for cards/buttons.
- No distracting animations in call controls.
- Respect existing reduced-motion/browser defaults.

## Product Copy Rules

Use product-facing labels:

- `Панель`
- `Комнаты`
- `Запланированные`
- `Шаблоны`
- `Создать комнату`
- `Подготовиться`
- `Войти в комнату`
- `Зал ожидания`
- `Участники`
- `Чат`
- `Демонстрация экрана`

Avoid visible copy:

- `LiveKit`
- `Reverb`
- `server issued identity`
- `testing media mode`
- raw room IDs
- debug connection state
- implementation terms like `application events`

Exception: technical names may remain in docs, tests, comments and hidden `data-test` nodes.

## Milestone D0 — Design Foundation

Scope:

- Define reusable design tokens/classes in `resources/css/app.css` only if needed.
- Audit current layouts and shared UI components.
- Decide which shadcn components must be installed before page work.
- Establish reusable app shell direction: sidebar, header, content width, mobile navigation.

Acceptance criteria:

- This plan exists and is committed.
- Required shadcn component gap list is confirmed.
- No app behavior changes.
- `npm run format:check` passes.

Status:

- [x] Completed

Notes:

- Existing shadcn component inventory confirmed.
- Gap list confirmed: install `switch`, `tabs`, `textarea`, `popover` or `command` only when milestone needs them.
- No runtime UI behavior changed in D0.

## Milestone D1 — App Shell & Navigation

Scope:

- Redesign authenticated shell and sidebar to match references.
- Keep current routes and active states.
- Remove low-value footer links if they feel like scaffolding, unless still needed.
- Make mobile navigation polished and usable.
- Replace visible internal/debug-looking text with product copy.

Target files:

- `resources/js/layouts/**`
- `resources/js/components/AppSidebar*.vue`
- `resources/js/components/Nav*.vue`
- related shadcn components if needed

Acceptance criteria:

- Authenticated pages share one polished shell.
- Sidebar is compact, product-like and mobile-safe.
- No broken navigation.
- Existing browser smoke for home/room navigation passes.

Status:

- [x] Completed

Notes:

- Authenticated shell now uses light SaaS background, polished sticky header and compact sidebar.
- Sidebar keeps only implemented routes: `Панель`, `Комнаты`.
- Starter-kit footer links removed from visible app shell.
- No new shadcn component required for D1.

## Milestone D2 — Public Welcome/Auth

Scope:

- Redesign public landing page using reference direction.
- Auth pages should feel part of the same product.
- Remove local/demo/system wording from visible public copy.
- Keep forms accessible and validation visible.

Target files:

- `resources/js/pages/Welcome.vue`
- `resources/js/pages/auth/**`
- `resources/js/layouts/auth/**`

Acceptance criteria:

- Public page communicates product value without internal implementation terms.
- Login/register/reset/2FA pages keep current behavior.
- Auth feature tests and smoke checks pass.

Status:

- [x] Completed

Notes:

- Public welcome page redesigned as light SaaS landing with product-facing copy.
- Visible public copy no longer exposes local/runtime implementation terms.
- Auth layouts now share the same Systemcall visual direction.
- Login, registration, password reset, password confirmation, email verification and 2FA form behavior kept unchanged.

## Milestone D3 — Dashboard

Scope:

- Redesign dashboard into product overview.
- Use cards for quick actions and feature summaries.
- Replace implementation-heavy text with SaaS copy.
- Use shadcn `Card`, `Button`, `Badge`.

Target files:

- `resources/js/pages/Dashboard.vue`

Acceptance criteria:

- Dashboard matches visual direction.
- Primary path to rooms is obvious.
- No debug/system visible copy.

Status:

- [x] Completed

Notes:

- Dashboard redesigned as product overview with hero, feature cards and quick actions.
- Primary route to rooms is visible in hero and quick-action section.
- Stack/local/debug copy removed from visible dashboard UI.
- Used existing shadcn `Card`, `Button` and `Badge`; no new component dependency needed.

## Milestone D4 — Rooms Index

Scope:

- Redesign `/rooms` based on reference.
- Improve room creation form using shadcn inputs/selects/cards.
- Add compact room list/card layout.
- Keep mobile layout first-class.
- Search/filter controls can be visual-only only if they do not imply unsupported behavior; otherwise defer or implement properly.

Target files:

- `resources/js/pages/rooms/Index.vue`
- room list/form components if extracted

Acceptance criteria:

- Create-room flow still works.
- Room list is compact and usable.
- Existing room browser tests pass.
- No fake unsupported controls unless disabled/hidden.

Status:

- [x] Completed

Notes:

- `/rooms` redesigned with compact product header, stats, creation card and room list cards.
- Create-room form keeps existing field names and submit behavior.
- Native visibility select kept to preserve browser test coverage and regular form serialization.
- Existing shadcn `Card`, `Button`, `Badge`, `Input` and `Label` reused; no new dependency needed.
- No fake search/filter controls added.

## Milestone D5 — Prepare Page

Scope:

- Redesign `/rooms/{id}/prepare` based on reference.
- Room info header with product icon and clean copy.
- Guest nickname card when unauthenticated.
- Device card with mic/camera selectors, preview, refresh and preview action.
- No raw room IDs or internal media wording.

Target files:

- `resources/js/pages/rooms/Prepare.vue`
- `resources/js/features/calls/components/DeviceSetupPanel.vue`
- reusable device/panel components if needed

Acceptance criteria:

- Guest cannot continue without nickname.
- Authenticated user flow remains unchanged.
- Device selection and preview still work without required mic/camera.
- Browser smoke for guest prepare and device selection passes.

Status:

- [x] Completed

Notes:

- Prepare page redesigned with product room header, separate guest nickname card and clear continue action.
- Device setup panel redesigned with selectors, preview, refresh and preview actions preserved.
- Existing `data-test` selectors and continue flow kept for browser tests.
- Raw room ID and internal media/runtime wording absent from visible prepare UI.

## Milestone D6 — Waiting Room

Scope:

- Redesign `/rooms/{id}/waiting`.
- Show current room, selected devices, preview and waiting state.
- Show other waiting participants as product UI.
- Moderator waiting-room panel should feel like a real review queue, not admin/debug output.

Target files:

- `resources/js/pages/rooms/Waiting.vue`
- `WaitingRoomStatusPanel.vue`
- `WaitingRoomModeratorPanel.vue`

Acceptance criteria:

- Pending/approved/rejected states clear.
- Approval/rejection smoke tests pass.
- No internal Reverb/LiveKit wording visible.

Status:

- [x] Completed

Notes:

- Waiting page redesigned with product room header, selected-device cards and matching device preview panel.
- Waiting status states now use clear pending/approved/rejected cards without internal runtime wording.
- Moderator panel redesigned as entry review queue with existing approve/reject hooks preserved.
- Other waiting participants shown as compact product cards.

## Milestone D7 — Call Experience

Scope:

- Redesign in-call layout while preserving Google Meet-like controls.
- Keep screen share/pinned tile layout: large main stage plus vertical participant rail.
- Participant hover actions need polished tooltips and permissions.
- Chat sheet/sidebar must match product system.
- Hide connection internals from visible UI while preserving `data-test` hooks.

Target files:

- `resources/js/pages/rooms/Call.vue`
- `ParticipantGrid.vue`
- `ParticipantTile.vue`
- `CallControlsBar.vue`
- `RoomChatSidebar.vue`
- moderation/application-state components used in call

Acceptance criteria:

- Audio/video/screen share/raised hand/chat/moderation browser tests pass.
- Active speaker highlight does not resize tiles.
- Tile ordering remains `pinned -> raised hand -> active speaker`.
- No visible debug/system copy.

Status:

- [x] Completed

Notes:

- In-call page redesigned as a light product workspace with hidden connection hooks kept only for tests.
- Participant grid keeps large screen-share/pinned stage with a scrollable vertical participant rail.
- Tile ordering now follows `pinned -> raised hand -> active speaker -> original order`.
- Participant tiles, hover moderation actions, call controls and chat sidebar were aligned with the design system and rounded-md surfaces.
- Visible call UI no longer exposes Reverb/LiveKit/internal runtime wording.

## Milestone D8 — Settings & Account

Scope:

- Align settings pages with design system.
- Keep security/passkey/2FA flows clear.
- Use shadcn cards, alerts, buttons and form components.

Target files:

- `resources/js/pages/settings/**`
- `resources/js/components/ManageTwoFactor.vue`
- `resources/js/components/DeleteUser.vue`

Acceptance criteria:

- Existing settings tests pass.
- Destructive actions are clearly separated.
- Forms remain accessible.

Status:

- [x] Completed

Notes:

- Profile, security and appearance settings pages now use shadcn card-based sections aligned with the product design system.
- Password, 2FA and recovery-code flows remain clear and accessible with existing form actions preserved.
- Account deletion is visually separated as a destructive red card and keeps the confirmation dialog/password check.
- Existing settings and two-factor tests pass.

## Milestone D9 — Polish & Regression

Scope:

- Full visible copy audit.
- Responsive pass for mobile/tablet/desktop.
- Accessibility pass for labels/buttons/contrast/focus.
- Remove unused UI code created during redesign.

Acceptance criteria:

- `npm run format:check`
- `npm run lint:check`
- `npm run types:check`
- `npm run build`
- Relevant `./vendor/bin/sail artisan test ... --compact`
- No known visual regressions in core flows.

Status:

- [ ] Pending

## Working Checklist For Each Milestone

1. Read this plan.
2. Inspect target files and sibling components.
3. Check available shadcn components.
4. Install missing shadcn component only when needed.
5. Implement the smallest coherent milestone.
6. Run focused browser/feature checks.
7. Run frontend checks.
8. Update this plan status.
9. Commit changes when requested.
