# Vito Desktop App Conversion Plan

Status: draft
Date: 2026-07-05

## Summary

Vito should become a desktop app by keeping the Laravel application mostly intact and adding a desktop runtime around it. The stable production path is a desktop shell that starts a local Laravel backend, supervises its background processes, stores all mutable data outside the installed app bundle, and opens the existing Inertia/React UI in a native desktop window.

Recommended production path:

1. Build a custom Electron shell.
2. Bundle a PHP runtime, preferably FrankenPHP if the required PHP 8.4 extensions are available.
3. Run Laravel locally on `127.0.0.1`.
4. Move desktop mode away from Redis/Horizon by default and use SQLite-backed queues plus file or database cache/session.
5. Run the websocket server, queue workers, and scheduler as supervised child processes.
6. Add per-platform installers, code signing, auto-update, and smoke tests.

NativePHP should still be spiked first because it may give a faster Laravel-native path. It should be accepted only if it handles Vito's PHP 8.4 extension needs, queue topology, scheduler, websocket child process, build signing, and updates without fragile custom work.

## Goals

- Ship a normal desktop installer for macOS, Windows, and Linux.
- Do not require users to install PHP, Composer, Node, Redis, Docker, or a web server.
- Preserve the existing Laravel/Inertia app as the primary product surface.
- Preserve long-running server-management workflows: SSH jobs, backups, metrics, SSL checks, websocket events, and scheduled tasks.
- Keep user data stable across app upgrades.
- Make process startup, shutdown, crash recovery, migrations, and updates deterministic.
- Keep Docker-based deployment untouched for the existing server product.

## Non-goals

- Rewriting the app as a native Swift, Kotlin, .NET, or Rust UI.
- Building a fully offline server-management tool. Vito still needs network access to managed servers and external APIs.
- Running the desktop backend as a system service after the app quits.
- Shipping Docker Desktop as a required dependency.
- Removing the hosted/web version architecture.

## Current Repository Constraints

- The app requires PHP `^8.4` plus `ext-ftp`, `ext-intl`, and `ext-zip` in `composer.json`.
- The frontend is already Inertia/React/Vite, so the UI can run inside Electron, NativePHP, or Tauri with minimal UI rewrite.
- The app currently depends on Redis for queues, cache, and session by default:
  - `config/queue.php` defaults to a Redis-backed `default` connection.
  - `config/cache.php` defaults to Redis.
  - `config/session.php` defaults to Redis.
- Horizon is required by Composer and used as the queue worker in Docker.
- The app already has a SQLite default database connection.
- A `jobs` migration already exists, so database queues are feasible.
- The app schedules many operational commands in `app/Console/Kernel.php`, including every-minute tasks.
- The app has a custom websocket command, `php artisan ws:serve`, defaulting to `127.0.0.1:8085`.
- The Docker runtime already proves the needed process set: web server, Redis, migrations, queue worker, scheduler/cron, and websocket.

## Decision: Production Architecture

Use a custom Electron shell with a bundled Laravel backend.

### Why Electron

Electron is heavier than Tauri, but it is the lowest-risk custom desktop shell for this app because:

- It has mature packaging, signing, auto-update, and installer ecosystems.
- It can supervise multiple child processes from a predictable Node main process.
- It lets the Laravel app continue serving normal HTTP routes to Chromium.
- NativePHP itself uses Electron under the hood for desktop, so the fallback is aligned with the Laravel-first path.

### Why Not Docker Desktop

Docker is useful for development and server deployment, but not for a stable consumer-style desktop app:

- It requires a large external dependency.
- Windows/macOS installation and permissions are support-heavy.
- Startup can be slow and resource-hungry.
- Updates become a mix of app updates, image updates, volumes, ports, and Docker state.
- It makes code signing and native app lifecycle harder.

### Why Reduce Redis/Horizon in Desktop Mode

Redis and Horizon are excellent for the server product, but they add a local service dependency to desktop packaging. For a single-user desktop app, SQLite-backed jobs are simpler and more stable:

- Fewer bundled binaries.
- Fewer ports and processes.
- Easier crash recovery.
- Easier backup and migration.
- Good enough queue throughput for local use.

Desktop mode should use:

```env
DB_CONNECTION=sqlite
QUEUE_CONNECTION=database
CACHE_DRIVER=file
SESSION_DRIVER=file
```

If database queues cannot handle a specific high-concurrency workload, add a desktop-only Redis sidecar later. Do not make Redis the first production desktop design.

## Architecture

### Process Model

The Electron main process is the desktop supervisor. It owns the lifecycle of all backend processes.

Required processes:

1. `vito-http`
   - Starts Laravel on a loopback HTTP port.
   - Preferred command: bundled FrankenPHP serving `public/`.
   - Fallback command: bundled PHP with a production-grade local server strategy.

2. `vito-queue`
   - Runs Laravel queue workers.
   - Command shape:

   ```sh
   php artisan queue:work database --queue=default,ssh,ssh-certbot --sleep=3 --timeout=1200 --tries=1
   ```

   - A separate long-timeout worker may be needed for backup jobs:

   ```sh
   php artisan queue:work database --queue=ssh,ssh-certbot --sleep=3 --timeout=3660 --tries=1
   ```

3. `vito-scheduler`
   - Runs scheduled tasks while the app is open.
   - Command:

   ```sh
   php artisan schedule:work
   ```

4. `vito-websocket`
   - Runs the existing websocket server.
   - Command:

   ```sh
   php artisan ws:serve --host=127.0.0.1 --port=<allocated-port>
   ```

Optional process:

5. `vito-redis`
   - Only if the database queue path fails acceptance testing.
   - Must be a bundled sidecar, never a user prerequisite.

### Startup Flow

1. Acquire a single-instance app lock.
2. Resolve the app data directory:
   - macOS: `~/Library/Application Support/Vito`
   - Windows: `%APPDATA%/Vito`
   - Linux: `~/.config/Vito`
3. Create app data subdirectories:
   - `storage/`
   - `storage/app/`
   - `storage/framework/`
   - `storage/logs/`
   - `storage/plugins/`
   - `runtime/`
4. Generate or load `.env.desktop`.
5. Allocate local ports for HTTP and websocket.
6. Write runtime env values:
   - `APP_URL=http://127.0.0.1:<http-port>`
   - `WS_HOST=127.0.0.1`
   - `WS_PORT=<ws-port>`
   - `WS_ALLOWED_ORIGINS=http://127.0.0.1:<http-port>`
7. Ensure `APP_KEY` exists.
8. Ensure SSH keypair exists.
9. Ensure SQLite database exists.
10. Run `php artisan migrate --force`.
11. Run safe optimization for production:
    - `php artisan optimize:clear`
    - `php artisan optimize`
12. Start `vito-websocket`.
13. Start `vito-queue`.
14. Start `vito-scheduler`.
15. Start `vito-http`.
16. Poll `/api/health` until ready.
17. Open the Electron window to `APP_URL`.

### Shutdown Flow

1. Stop accepting new window actions.
2. Ask Laravel workers to quit gracefully where possible.
3. Stop scheduler.
4. Stop websocket server.
5. Stop HTTP server.
6. Kill remaining child processes after a timeout.
7. Release the single-instance lock.

### Crash Recovery

Each process must have a restart policy:

- `vito-http`: restart up to 3 times in 60 seconds, then show a fatal backend error screen.
- `vito-websocket`: restart indefinitely with backoff, show a degraded realtime status if down.
- `vito-queue`: restart indefinitely with backoff, surface worker status in diagnostics.
- `vito-scheduler`: restart indefinitely with backoff, surface scheduler status in diagnostics.

All child process stdout/stderr must be written to:

```text
<app-data>/storage/logs/desktop-processes.log
```

## Laravel Changes

### Desktop Runtime Detection

Add a small desktop runtime helper instead of scattering `env()` checks.

Suggested file:

```text
app/Support/DesktopRuntime.php
```

Responsibilities:

- `enabled(): bool`
- `storagePath(): ?string`
- `httpPort(): ?int`
- `websocketPort(): ?int`
- `dataPath(string $path = ''): string`

Environment flag:

```env
VITO_DESKTOP=true
```

### Mutable Storage Path

The installed app bundle must be treated as read-only. Desktop mode must move Laravel `storage_path()` to the per-user app data directory.

Update `bootstrap/app.php` after the application is created:

```php
if (($storagePath = $_ENV['VITO_STORAGE_PATH'] ?? null) !== null) {
    $app->useStoragePath($storagePath);
}
```

The Electron shell should set:

```env
VITO_STORAGE_PATH=<app-data>/storage
```

### Desktop Env File

Add:

```text
.env.desktop.example
```

Baseline:

```env
APP_NAME=Vito
APP_ENV=production
APP_DEBUG=false
APP_URL=http://127.0.0.1
APP_KEY=

VITO_DESKTOP=true
VITO_DATA_PATH=
VITO_ENV_PATH=
VITO_STORAGE_PATH=

APP_SERVICES_CACHE=
APP_PACKAGES_CACHE=
APP_CONFIG_CACHE=
APP_ROUTES_CACHE=
APP_EVENTS_CACHE=
VIEW_COMPILED_PATH=

DB_CONNECTION=sqlite
DB_DATABASE=database.sqlite

QUEUE_CONNECTION=database
DB_QUEUE=default
QUEUE_FAILED_DRIVER=database-uuids

CACHE_DRIVER=file
SESSION_DRIVER=file
FILESYSTEM_DISK=local

WS_HOST=127.0.0.1
WS_PORT=8085
WS_ALLOWED_ORIGINS=
WS_BROADCAST_SECRET=

MAIL_MAILER=log
```

### Queue Configuration

Add an explicit database queue connection to `config/queue.php`:

```php
'database' => [
    'driver' => 'database',
    'connection' => env('DB_CONNECTION', 'sqlite'),
    'table' => 'jobs',
    'queue' => env('DB_QUEUE', 'default'),
    'retry_after' => max(300, (int) env('BACKUP_RUN_TIMEOUT', 3600)) + 60,
    'after_commit' => false,
],
```

Desktop workers must consume all relevant queue names:

```text
default,ssh,ssh-certbot
```

### Cache And Session

Use file cache and file sessions for the first desktop build. This avoids adding a cache table migration.

If file locking becomes unreliable on Windows under load, switch desktop cache/session to database and add the standard Laravel cache table migration.

### Horizon

Do not run Horizon in desktop mode.

Options:

1. Keep `laravel/horizon` installed for server deployments.
2. Disable Horizon routes/features in desktop mode if needed.
3. Add a lightweight desktop diagnostics page later that reads queue state from the `jobs` and `failed_jobs` tables.

### Websocket Configuration

The websocket port must become dynamic in desktop mode. Avoid hardcoding `8085` because it may already be in use.

Electron should allocate the port and pass it through env. Laravel should expose the websocket URL in the frontend bootstrap data.

### Desktop Authentication

Desktop mode should keep Laravel's session guard, but replace the normal web auth pages with a desktop-local user picker.

Implemented behavior:

1. Desktop-only routes live under `/desktop`.
2. Unauthenticated desktop requests redirect to `desktop.login`.
3. The desktop login screen lists existing local users.
4. Before the app has been locked, choosing a user logs in without showing the normal email/password page.
5. Locking the desktop app logs the current user out, marks the session as locked, and redirects to the desktop login screen.
6. Unlocking after a lock requires the selected user's password.
7. Fortify login, password reset, password confirmation, and two-factor challenge views redirect back to the desktop picker in desktop mode.
8. If no users exist, the desktop login screen becomes a first-run setup form that creates the first local admin user, creates its default project, and logs it in.

Desktop must not ship with a fixed default password. The setup route must stay desktop-only and must reject requests after any user exists. During development and smoke testing, `php artisan user:create` remains a valid way to seed users manually.

## Desktop Shell Structure

Suggested repo layout:

```text
desktop/
  package.json
  tsconfig.json
  electron-builder.yml
  src/
    main.ts
    preload.ts
    process-supervisor.ts
    runtime-paths.ts
    first-run.ts
    health-check.ts
    logging.ts
  resources/
    bin/
      mac-arm64/
      mac-x64/
      win-x64/
      linux-x64/
    icons/
```

Generated build artifact layout:

```text
desktop/dist/
  app/
    artisan
    bootstrap/
    config/
    database/
    public/
    resources/
    routes or route-attribute cache output
    storage-template/
    vendor/
```

Do not copy development-only files into the desktop app:

- `node_modules/`
- `tests/`
- `.git/`
- local `.env`
- Docker files unless needed for source compliance bundles

## Build Pipeline

### Local Build Script

Add a top-level script later:

```sh
composer desktop:prepare
npm run desktop:build
```

The preparation step should:

1. Install Composer dependencies with `--no-dev --optimize-autoloader`.
2. Run `npm ci`.
3. Run `npm run build`.
4. Create a clean Laravel distribution directory.
5. Copy required runtime files.
6. Validate required PHP extensions against the bundled PHP binary.
7. Package Electron.

### CI Matrix

Build on each target OS, not by cross-compiling everything from one machine:

- macOS arm64
- macOS x64 if supported
- Windows x64
- Linux x64

Each CI job must run:

```sh
composer install
npm ci
npm run build
php artisan test
desktop build
desktop smoke test
```

### Code Signing And Notarization

macOS:

- Sign the app bundle.
- Notarize release artifacts.
- Verify first launch on a clean machine.

Windows:

- Sign installer and executable.
- Prefer MSIX or NSIS depending on updater choice.

Linux:

- AppImage first for portability.
- Add `.deb` later if there is demand.

### Auto Updates

Preferred Electron path:

- Use `electron-builder` plus `electron-updater`.
- Host releases on GitHub Releases, S3, or a dedicated update endpoint.
- Stable and beta channels should be separate.

Update safety requirements:

- Never overwrite app data.
- Run migrations only after the new backend starts.
- Backup the SQLite database before migrations.
- Roll back the app binary if health checks fail after update.
- Keep release notes visible before applying updates.

## NativePHP Spike

Run this before committing fully to custom Electron.

### Spike Steps

1. Create a throwaway branch.
2. Install NativePHP Desktop.
3. Add `.env.desktop.example`.
4. Configure database queues, file cache, and file sessions.
5. Configure NativePHP queue workers for:
   - `default`
   - `ssh`
   - `ssh-certbot`
6. Start `ws:serve` as a persistent child process.
7. Verify scheduler behavior.
8. Build a macOS app locally.
9. Confirm the app launches without installed PHP, Composer, Node, Redis, or Docker.
10. Confirm required PHP extensions:
    - ftp
    - intl
    - zip
    - sqlite/pdo_sqlite
    - openssl
    - curl
11. Run core workflows:
    - first-run admin creation
    - server creation flow up to SSH validation
    - queue job execution
    - websocket terminal/events
    - scheduled metrics/check tasks
    - app restart
    - migration after version bump

### Accept NativePHP If

- Packaged app starts reliably on macOS and at least one other target OS.
- Required PHP extensions are available or custom binaries are practical.
- Queue workers can process `default`, `ssh`, and `ssh-certbot` without Horizon.
- Scheduler runs while the app is open.
- `ws:serve` can be supervised and restarted.
- User storage lives outside the signed app bundle.
- Auto-update and signing path is acceptable.

### Reject NativePHP If

- PHP 8.4 or required extensions require unsupported custom binaries.
- Background workers or websocket process supervision is fragile.
- Build customization requires forking too much NativePHP internals.
- Update/signing behavior cannot be tested cleanly.

If rejected, continue with the custom Electron path using lessons from the spike.

## Tauri Option

Tauri remains a viable second fallback if app size becomes a hard requirement. It should not be the first implementation because it requires more custom process supervision and less Laravel-specific tooling.

Use Tauri only if:

- Electron bundle size is unacceptable.
- The team is comfortable maintaining Rust/Tauri process orchestration.
- The sidecar binary pipeline for PHP/FrankenPHP is proven on all target OSes.

## Security Requirements

- Bind all local services to `127.0.0.1`, never `0.0.0.0`.
- Use dynamic ports where possible.
- Enforce single-instance mode.
- Set Electron `nodeIntegration: false`.
- Set Electron `contextIsolation: true`.
- Expose only minimal APIs through `preload.ts`.
- Restrict websocket allowed origins to the current local app URL.
- Generate a desktop-local broadcast secret on first run.
- Do not ship default credentials.
- Do not store secrets in the installed app bundle.
- Keep private SSH keys in the app data directory with restrictive permissions.
- Redact secrets in desktop process logs.

## Diagnostics

Add a desktop diagnostics screen or command before public release.

It should show:

- App version.
- Backend version.
- App data path.
- HTTP port.
- Websocket port.
- Database path.
- Process status for HTTP, queue, scheduler, websocket.
- Last 200 lines of desktop process logs.
- Last 200 lines of Laravel logs.
- Queue depth by queue name.
- Failed job count.
- Button to export a support bundle.

Support bundle must redact:

- APP_KEY
- WS_BROADCAST_SECRET
- API tokens
- SSH private keys
- provider credentials

## Testing Plan

### Unit And Feature Tests

Keep existing tests. Add desktop-specific feature tests for:

- desktop storage path override
- desktop env generation
- database queue config
- first-run user creation guard
- websocket URL generation
- desktop user picker login
- desktop lock and password unlock

### Process Integration Tests

Run from the desktop package:

1. Start app.
2. Wait for `/api/health`.
3. Assert SQLite database exists in app data.
4. Assert migrations are applied.
5. Assert queue worker processes a test job.
6. Assert scheduler process is running.
7. Assert websocket port accepts connection.
8. Restart app and verify no duplicate processes.
9. Quit app and verify child processes are gone.

### Packaging Smoke Tests

For each target OS:

1. Install fresh app.
2. Launch app.
3. Complete first-run admin setup.
4. Reload app.
5. Quit app.
6. Relaunch app.
7. Verify login persists.
8. Verify logs are created in app data.
9. Verify no writes happen inside the signed app bundle.

### Update Tests

1. Install version N.
2. Create user and sample data.
3. Install/update to version N+1.
4. Verify app data remains.
5. Verify migrations run once.
6. Verify rollback behavior on forced failed health check.

## Implementation Phases

### Phase 0: NativePHP Decision Spike

Deliverables:

- Spike branch.
- Written decision note.
- List of extension/build blockers.
- Pass/fail against acceptance criteria.

Exit:

- Choose NativePHP or custom Electron.

### Phase 1: Desktop Runtime Foundation

Deliverables:

- `.env.desktop.example`
- `VITO_DESKTOP` runtime detection.
- `VITO_STORAGE_PATH` support in `bootstrap/app.php`.
- Database queue connection in `config/queue.php`.
- File cache/session desktop profile.
- First-run admin creation plan or route.

Exit:

- Laravel can run locally in desktop mode without Redis.

### Phase 2: Electron Shell Prototype

Deliverables:

- `desktop/` package.
- Electron main window.
- Runtime path resolver.
- Process supervisor.
- Health check polling.
- App data directory creation.
- Dynamic port allocation.

Exit:

- `npm run desktop:dev` launches Vito through Electron.

### Phase 3: Background Process Reliability

Deliverables:

- Supervised HTTP, queue, scheduler, websocket processes.
- Restart/backoff policies.
- Unified process logging.
- Single-instance lock.
- Graceful shutdown.

Exit:

- App restart/quit leaves no orphaned child processes.

### Phase 4: Desktop UX And Diagnostics

Deliverables:

- First-run admin setup.
- Backend starting screen.
- Backend error screen.
- Diagnostics/export support bundle.
- Update/restart prompts.

Exit:

- Non-technical user can install, start, diagnose, and update the app.

### Phase 5: Packaging And Signing

Deliverables:

- macOS signed/notarized build.
- Windows signed installer.
- Linux AppImage.
- CI matrix builds.
- Release artifact publishing.

Exit:

- A clean machine can install and run each platform build.

### Phase 6: Auto Update

Deliverables:

- Stable and beta update channels.
- Update download/install flow.
- Database backup before migration.
- Failed-update health check handling.

Exit:

- Version N updates to N+1 without data loss.

### Phase 7: Beta Hardening

Deliverables:

- 20 to 30 real workflow test runs.
- Long-running backup/SSH job tests.
- Sleep/wake tests.
- Offline/online transition tests.
- Port conflict tests.
- Antivirus/Defender check on Windows.

Exit:

- Known blocking bugs fixed or explicitly accepted.

## Work Checklist

- [x] Decide NativePHP vs custom Electron: custom Electron shell bundling a static PHP built with static-php-cli (the same runtime approach NativePHP uses).
- [x] Add desktop env profile.
- [x] Add storage path override.
- [x] Add database queue connection.
- [x] Confirm jobs table migration covers all queue needs.
- [x] Remove Redis requirement from desktop mode.
- [ ] Disable Horizon runtime in desktop mode.
- [x] Make websocket port dynamic.
- [x] Add desktop first-run admin setup.
- [x] Add Electron shell.
- [x] Add process supervisor.
- [x] Add app data path management.
- [x] Add health check based launch.
- [x] Add graceful shutdown.
- [ ] Add diagnostics.
- [x] Add desktop smoke tests (scripts/desktop/smoke-test.sh, run in CI).
- [x] Add packaging (electron-builder + scripts/desktop + desktop-release workflow).
- [x] Add code signing (optional GitHub secrets, unsigned builds without them).
- [ ] Add auto-update.
- [ ] Run beta hardening matrix.

## Open Questions

- Which platforms are required for v1: macOS only, or macOS plus Windows/Linux?
- Does the desktop app need to run jobs while the window is closed but the app is still in the tray?
- Should desktop support plugins from `storage/plugins/*/*` in v1?
- Should the desktop app use the same AGPL distribution model, or dual-license the desktop release?
- Should updates be hosted on GitHub Releases, S3, or the Vito website?
- Is app-store distribution required, or are direct downloads enough?
- Should the first desktop beta keep Horizon available behind a debug flag for comparison?

## Initial Recommendation

Run the NativePHP spike first, but plan production around custom Electron unless the spike clears every process, extension, and update requirement cleanly.

The custom Electron path is more work, but it gives Vito direct control over the exact things that matter for stability: process supervision, local data paths, updates, diagnostics, and platform-specific packaging.
