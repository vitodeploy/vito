# Vito Desktop

The desktop shell runs the Laravel app as a local backend and opens the existing Inertia UI in an Electron window. Everything ships in a single installer: the Electron shell, the Laravel application, the built frontend assets, and a portable PHP 8.4 runtime. No PHP, Redis, Docker, or web server is required on the user's machine.

## Architecture

The Electron main process supervises four backend processes, all bound to `127.0.0.1` on dynamically allocated ports:

| Process | Command |
| --- | --- |
| `vito-http` | `php -S 127.0.0.1:<port> -t public <framework router>` |
| `vito-queue` | `php artisan queue:work database --queue=default,ssh,ssh-certbot` |
| `vito-scheduler` | `php artisan schedule:work` |
| `vito-websocket` | `php artisan ws:serve --host=127.0.0.1 --port=<port>` |

Desktop mode uses SQLite, database queues, and file cache/sessions. All mutable data lives outside the app bundle:

- macOS: `~/Library/Application Support/Vito`
- Windows: `%APPDATA%/Vito`
- Linux: `~/.config/Vito`

## Development

```sh
npm install --prefix desktop
npm run dev            # or: npm run build:desktop (desktop assets)
npm run desktop:dev
```

The dev shell uses the repository root as the Laravel app path and the system PHP binary unless `PHP_BINARY` is set. Frontend assets come from the Vite dev server (`public/hot`) or from `public/build-desktop` (`npm run build:desktop`), never from the committed `public/build`.

## Packaging locally

```sh
composer install --no-dev --optimize-autoloader
npm install
npm run build:desktop
bash scripts/desktop/build-app.sh
bash scripts/desktop/fetch-php.sh darwin arm64      # platform: darwin|linux|win32, arch: arm64|x64
bash scripts/desktop/smoke-test.sh "$PWD/desktop/resources/bin/darwin/arm64/php"
npm --prefix desktop ci
npm run desktop:build
npm run desktop:package -- --mac --arm64            # --win --x64 / --linux --x64
```

Installers land in `desktop/release/`.

`fetch-php.sh` builds a static PHP with [static-php-cli](https://github.com/crazywhalecc/static-php-cli) on macOS/Linux (slow the first time, cached afterwards) and downloads the official php.net build on Windows.

## Releasing

The `releases` workflow builds and attaches desktop installers (macOS arm64/x64 dmg+zip, Windows x64 NSIS installer, Linux x86_64 AppImage) to every GitHub release automatically. The `desktop-release` workflow can also be dispatched on its own with an existing release tag to (re)build and upload the desktop apps for that release.

### Code signing secrets (all optional)

Without these secrets the workflows produce unsigned builds (macOS users must right-click → Open on first launch).

| Secret | Purpose |
| --- | --- |
| `DESKTOP_MAC_CSC_LINK` | Developer ID Application certificate (.p12, base64) |
| `DESKTOP_MAC_CSC_KEY_PASSWORD` | Password for the .p12 |
| `DESKTOP_APPLE_ID` | Apple ID for notarization |
| `DESKTOP_APPLE_APP_SPECIFIC_PASSWORD` | App-specific password for notarization |
| `DESKTOP_APPLE_TEAM_ID` | Apple team ID for notarization |
| `DESKTOP_WIN_CSC_LINK` | Windows code signing certificate (.pfx, base64) |
| `DESKTOP_WIN_CSC_KEY_PASSWORD` | Password for the .pfx |

Notarization runs automatically when the three `DESKTOP_APPLE_*` secrets are present together with the macOS certificate.
