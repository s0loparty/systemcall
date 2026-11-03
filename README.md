# Systemcall

Сервис групповых аудио- и видеозвонков на Laravel, Vue 3, TypeScript, PostgreSQL, Redis, Laravel Reverb и LiveKit.

Проект сейчас реализует базовый vertical slice видеозвонков: комнаты, участники, LiveKit token issuance, media UI, Reverb application events, waiting room, модерация, чат, raised hands, pinning и subrooms. Текущий статус milestones см. в `docs/video-calls/IMPLEMENTATION_PLAN.md`.

## Стек

- Laravel 13
- PHP 8.3 для локальной разработки и CI
- PHP 8.4 в production Docker image
- Vue 3 + TypeScript + Inertia
- TailwindCSS 4
- PostgreSQL
- Redis
- Laravel Reverb
- LiveKit
- FrankenPHP + Laravel Octane
- Docker Compose
- Dokploy

## Локальная разработка

Целевая локальная среда: Windows 10 + WSL2 + Laravel Sail.

Основной production `compose.yaml` предназначен для Dokploy. Для локальной разработки используйте сохранённый Sail compose:

```bash
cp .env.example .env
SAIL_FILES=compose.sail.yaml ./vendor/bin/sail up -d
SAIL_FILES=compose.sail.yaml ./vendor/bin/sail composer install
SAIL_FILES=compose.sail.yaml ./vendor/bin/sail npm install
SAIL_FILES=compose.sail.yaml ./vendor/bin/sail artisan key:generate
SAIL_FILES=compose.sail.yaml ./vendor/bin/sail artisan migrate
SAIL_FILES=compose.sail.yaml ./vendor/bin/sail npm run dev
```

Чтобы не повторять `SAIL_FILES`, добавьте в локальный `.env`:

```env
SAIL_FILES=compose.sail.yaml
APP_SERVICE=laravel.test
```

После этого можно запускать короче:

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate
./vendor/bin/sail npm run dev
```

Локальный адрес приложения зависит от `APP_PORT`; по умолчанию Sail публикует приложение на `http://localhost`.

## Локальная проверка видеозвонков

Для проверки Reverb и LiveKit локально используйте дополнительный Sail override:

```env
SAIL_FILES=compose.sail.yaml:compose.sail-livekit.yaml
APP_SERVICE=laravel.test
APP_URL=http://localhost
```

Override добавляет:

- `reverb` на `localhost:8080`;
- `livekit` signaling на `ws://localhost:7880`;
- LiveKit media ports `7881/tcp`, `3478/udp`, `50000-50020/udp`;
- локальные Reverb credentials `local-app` / `local-key` / `local-secret`;
- локальные LiveKit credentials `devkey` / `local-livekit-secret-local-livekit-secret`;
- `QUEUE_CONNECTION=sync`, чтобы application events сразу уходили в Reverb без отдельного queue worker.

Запуск:

```bash
./vendor/bin/sail up -d --build
./vendor/bin/sail artisan optimize:clear
./vendor/bin/sail artisan migrate
./vendor/bin/sail npm run dev
```

Проверка сервисов:

```bash
./vendor/bin/sail ps
curl -I http://localhost
curl -I http://localhost:7880
curl -I http://localhost:8080
```

Создать локальную demo-комнату можно через `tinker`:

```bash
./vendor/bin/sail artisan tinker --execute '$user = \App\Models\User::firstOrCreate(["email" => "demo@example.com"], ["name" => "Demo Owner", "password" => \Illuminate\Support\Facades\Hash::make("password")]); $room = app(\App\Actions\Rooms\CreateRoomAction::class)->handle($user, ["name" => "Local demo call", "visibility" => "public", "waiting_room_enabled" => false]); echo route("rooms.call.show", $room).PHP_EOL;'
```

Откройте выведенный URL в двух разных браузерах или в обычном и private window. В первом браузере войдите как `demo@example.com` / `password`; во втором можно зайти как guest. Для проверки subrooms создайте subroom owner-ом в панели `Subrooms`, затем переключайтесь между `Main room` и subroom.

Если Docker Desktop/WSL2 начинает подвисать на UDP-портах, оставьте локальный диапазон `50000-50020/udp`. Production должен использовать полный диапазон из `compose.yaml`: `50000-60000/udp`.

## Локальные проверки

Запускайте проверки внутри Sail:

```bash
./vendor/bin/sail test --compact
./vendor/bin/sail composer types:check
./vendor/bin/sail npm run lint:check
./vendor/bin/sail npm run format:check
./vendor/bin/sail npm run types:check
./vendor/bin/sail npm run build
```

Browser/E2E smoke запускается отдельно. Он использует собранные assets из
`public/build`, поэтому перед запуском обновите build и установите браузер
Playwright в контейнере:

```bash
./vendor/bin/sail npm run build
./vendor/bin/sail npm run playwright:install
```

Минимальный browser smoke:

```bash
./vendor/bin/sail composer test:browser
```

Полный E2E alias сейчас указывает на browser suite:

```bash
./vendor/bin/sail composer test:e2e
```

План расширения E2E сценариев для комнат, аудио, видео, screen sharing и
поднятия руки описан в `docs/video-calls/E2E_TESTING_PLAN.md`.

Проверка production Compose:

```bash
docker compose config
```

## Production deployment

Production рассчитан на один VPS под управлением Dokploy.

Dokploy владеет:

- Docker orchestration
- host-level Traefik
- HTTP/HTTPS routing
- TLS certificates
- public ports `80/tcp` и `443/tcp`

В проектном `compose.yaml` не должно быть отдельного Traefik service.

Production services:

```text
app
worker
scheduler
reverb
postgres
redis
livekit
```

`app`, `worker`, `scheduler` и `reverb` используют один production image Laravel-приложения. `app` работает через FrankenPHP + Laravel Octane, не через `php artisan serve`.

## GitHub Actions: Variables и Secrets

Откройте репозиторий GitHub: **Settings → Secrets and variables → Actions**.
Добавляйте значения на уровне репозитория: публичные настройки во вкладке
**Variables**, токен Dokploy во вкладке **Secrets**.

### Variables для сборки образа

| Имя | Обязательно | Значение / пример |
| --- | --- | --- |
| `VITE_REVERB_APP_KEY` | Да | Публичный ключ Reverb, точно совпадающий с `REVERB_APP_KEY` в Dokploy |
| `VITE_REVERB_HOST` | Да | Публичный hostname Reverb без протокола и пути, например `ws.call.example.com` |
| `VITE_APP_NAME` | Нет | Название приложения; по умолчанию `Systemcall` |
| `VITE_REVERB_PORT` | Нет | Публичный порт Reverb; по умолчанию `443` |
| `VITE_REVERB_SCHEME` | Нет | По умолчанию `https` |

Для сборки и публикации достаточно двух обязательных Variables. `VITE_*`
встраиваются в frontend и доступны браузеру: это не место для секретов. После
изменения этих значений запустите CI заново, чтобы собрать и опубликовать новый
образ. Изменение только runtime-переменных Dokploy не обновит frontend.

### Variables и Secrets для автодеплоя

Эти настройки нужны только для автоматического запуска деплоя в Dokploy после
успешной публикации образа из ветки `production`.

| Тип | Имя | Значение / пример |
| --- | --- | --- |
| Variable | `DOKPLOY_DEPLOY_ENABLED` | `true` для включения автодеплоя; если отсутствует или не равно `true`, деплой выполняется вручную |
| Variable | `DOKPLOY_URL` | HTTPS-адрес панели Dokploy, например `https://dokploy.example.com`, без `/api` в конце |
| Variable | `DOKPLOY_COMPOSE_ID` | ID существующего Compose-сервиса в Dokploy, не имя проекта или контейнера |
| Secret | `DOKPLOY_API_KEY` | API-токен Dokploy с правом деплоя указанного Compose-сервиса |

API-токен создаётся в профиле Dokploy. ID Compose-сервиса можно найти в ответе
Dokploy API `project.all`. Панель должна быть доступна GitHub runner по HTTPS.
Ветка `main` публикует образ, но не запускает production-деплой.

### Доступ к GHCR и настройка Dokploy

`GITHUB_TOKEN` создаётся GitHub автоматически: добавлять такой Secret или отдельный
GitHub PAT для публикации не нужно. Workflow выдаёт задаче публикации право
`packages: write`; политика репозитория/организации должна разрешать публикацию
пакетов.

В Dokploy задайте `APP_IMAGE=ghcr.io/s0loparty/systemcall:production`. Если пакет
приватный, настройте там доступ к registry `ghcr.io`: GitHub username и PAT
(classic) с `read:packages`, имеющий доступ к пакету. Этот PAT хранится в настройках
registry Dokploy, а не в GitHub Actions Secrets. Для публичного пакета авторизация
при скачивании не нужна.

Отключите деплой по Git push в Dokploy, чтобы он не запускался раньше завершения
CI и публикации образа. Для автодеплоя используйте настройки выше; для ручного
деплоя дождитесь успешной задачи `Production image` и нажмите Deploy в Dokploy.

`APP_KEY`, `DB_PASSWORD`, `REVERB_APP_SECRET` и `LIVEKIT_API_SECRET` остаются в
Dokploy. Они не нужны сборке и не должны передаваться в Docker build arguments.
Полная инструкция: [DEPLOYMENT.md](docs/video-calls/DEPLOYMENT.md).

## Dokploy domains

Создайте DNS записи на публичный IP VPS:

```text
call.example.com
ws.call.example.com
livekit.example.com
```

В Dokploy настройте routing:

```text
call.example.com      -> app:8000
ws.call.example.com   -> reverb:8080
livekit.example.com   -> livekit:7880
```

Включите HTTPS для всех публичных доменов.

## VPS firewall

Открыть:

```text
80/tcp
443/tcp
7881/tcp
50000-60000/udp
3478/udp
```

Не открывать наружу:

```text
5432
6379
```

`443/tcp` принадлежит Dokploy Traefik. LiveKit не должен занимать host `443/tcp` в текущей single-VPS схеме.

## Production environment variables

Secrets хранить в Dokploy, не в Git.

Минимально заполнить:

```env
APP_IMAGE=ghcr.io/s0loparty/systemcall:production
APP_NAME=Systemcall
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://call.example.com

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=systemcall
DB_USERNAME=systemcall
DB_PASSWORD=generate-long-random-password

REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=

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
REVERB_APP_ID=
REVERB_APP_KEY=
REVERB_APP_SECRET=
REVERB_SCALING_ENABLED=true

VITE_APP_NAME=Systemcall
VITE_REVERB_APP_KEY=
VITE_REVERB_HOST=ws.call.example.com
VITE_REVERB_PORT=443
VITE_REVERB_SCHEME=https

LIVEKIT_URL=https://livekit.example.com
LIVEKIT_API_KEY=
LIVEKIT_API_SECRET=
```

`VITE_REVERB_APP_KEY` должен совпадать с `REVERB_APP_KEY`. Источник `VITE_*` для
сборки образа — GitHub Actions Variables из раздела выше. После их изменения
пересоберите образ через CI и задеплойте его в Dokploy.

## Production deploy flow

1. Push production branch.
2. GitHub Actions runs checks, builds and smoke-tests the image, then publishes it to GHCR.
3. Dokploy pulls the image and deploys `compose.yaml`, manually or through the optional CI deployment job.
4. Run one-shot migration in the application container:

```bash
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan event:cache
```

5. Verify `app`, `worker`, `scheduler`, `reverb`, `postgres`, `redis`, `livekit`.
6. Verify Dokploy routes and HTTPS certificates.
7. Verify LiveKit signaling and media ports.

Do not run unconditional migrations in every long-running container startup command.

## Документация

Подробная документация по видеозвонкам:

- `docs/video-calls/IMPLEMENTATION_PLAN.md`
- `docs/video-calls/ARCHITECTURE.md`
- `docs/video-calls/LIVEKIT.md`
- `docs/video-calls/DEPLOYMENT.md`
- `docs/video-calls/QUALITY_CHECKLIST.md`
