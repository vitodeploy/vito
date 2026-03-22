# Copilot Instructions — Vito

Vito is a self-hosted server management tool. Stack: Laravel 12 (L10 structure), PHP 8.4, Inertia v2, React 19, Tailwind v4, PHPUnit 11.

## Architecture

- **Actions pattern**: All business logic lives in `app/Actions/`. Controllers are thin — they call an Action and return the response. Never put business logic in controllers.
- **Validation in Actions**, not in controllers or Form Request classes. Use `Validator::make()` inside the Action.
- **Policies** for authorization. Use the `HasRolePolicies` trait which provides `hasReadAccess()`, `hasWriteAccess()`, and `hasOwnerAccess()` checks against the project.
- **Models** extend `AbstractModel` (not `Model` directly). It includes `HasTimezoneTimestamps` and a `jsonUpdate()` helper.
- **Enums** implement the `VitoEnum` contract (`app/Contracts/VitoEnum.php`) which requires `getColor(): string` and `getText(): string`.
- **API Resources** (`app/Http/Resources/`) for all JSON responses. Call `->getText()` / `->getColor()` on enum properties — never return raw enum values.
- **Queued Jobs** use `Queueable` + `UniqueQueue` traits. `UniqueQueue` prevents duplicate jobs via cache locks.
- **SSH** is executed via `app/Helpers/SSH.php` and the `SSH` facade. Never shell out directly. Use `SSH::fake()` in tests.
- **Providers** (server, DNS, storage, source control) follow an Interface + AbstractProvider pattern. New providers extend the abstract class.
- **Services** (database, webserver, PHP, etc.) extend `AbstractService` and implement `ServiceInterface`.
- **Route attributes**: Controllers use `Spatie\RouteAttributes` (`#[Get]`, `#[Post]`, `#[Prefix]`, `#[Middleware]`).

## Error Handling

- Use **validation exceptions (422)** for user input errors — never return 400 status codes.
- Let provider/service errors **bubble up** — don't silently catch and suppress exceptions.
- Wrap multi-step data mutations in **database transactions** to prevent partial state.

## Migrations

- Combine related schema changes into a **single migration file** per feature.
- Migrations handle schema and data only — never dispatch jobs, SSH commands, or server-side actions.
- Never run `migrate:fresh` or other destructive database commands.

## Testing

- PHPUnit only. `RefreshDatabase` trait. Use factories.
- `TestCase` provides `$this->user`, `$this->server`, `$this->site` with services pre-configured.
- Use `SSH::fake()` for SSH operations, `Http::fake()` for HTTP calls.
- Don't over-test trivial logic that PHP handles natively.

## Topic-specific guidelines

See `.github/instructions/` for detailed standards on PHP/Laravel, frontend, and security.
