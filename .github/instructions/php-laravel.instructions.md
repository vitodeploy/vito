---
applyTo: "**/*.php"
description: "PHP and Laravel coding standards, Actions pattern, Eloquent conventions"
---

# PHP & Laravel Guidelines

## PHP Style

- Always use curly braces for control structures.
- Use constructor property promotion.
- Declare explicit return types on all methods.
- Use PHPDoc blocks for documentation — no inline `//` comments inside function or method bodies. Comments are allowed in migrations.
- Use array shapes in PHPDoc where appropriate.

## Models

- All models extend `AbstractModel` (`app/Models/AbstractModel.php`), not `Illuminate\Database\Eloquent\Model`.
- `AbstractModel` includes the `HasTimezoneTimestamps` trait and provides `jsonUpdate()` for updating JSON columns.
- Cast enum properties to their enum class (e.g., `'status' => ServerStatus::class`).
- Use `'encrypted:json'` cast for sensitive data like credentials.
- Use PHPDoc `@property` annotations for relationship collections.

## Eloquent & Database

- Use `Model::query()->...` instead of `DB::` facade for queries.
- Eager load relationships to prevent N+1 queries.
- Use `config()` helper — never call `env()` outside of config files.

## Enums

- All enums must implement the `VitoEnum` contract (`app/Contracts/VitoEnum.php`).
- `VitoEnum` requires two methods: `getColor(): string` and `getText(): string`.
- `getColor()` returns a UI color string (e.g., `'success'`, `'danger'`, `'warning'`, `'gray'`).
- `getText()` typically returns `$this->value`.
- In API Resources, always call `->getText()` and `->getColor()` — never return raw enum values.

## Actions Pattern

All business logic belongs in Action classes under `app/Actions/`.

- Method names match the operation: `create()`, `update()`, `handle()`, `run()`, `install()`, etc.
- Validate input inside the Action using `Validator::make()`. Extract validation to a private `validate()` method when rules are complex.
- Keep Actions focused — compose multiple Actions rather than building monolithic ones.
- Controllers should only: receive the request, call the Action, and return the response.

## Policies

- Use Policy classes (`app/Policies/`) for all authorization.
- Use the `HasRolePolicies` trait, which provides `hasReadAccess()`, `hasWriteAccess()`, and `hasOwnerAccess()` based on user role in the project.
- Never do inline permission checks in controllers or actions.

## Jobs

- Implement `ShouldQueue`. Use both `Queueable` and `UniqueQueue` traits.
- `UniqueQueue` prevents duplicate jobs via cache locks — wrap work in `$this->run($key, callable)`.
- Implement a `failed(Exception $e)` method for error handling / status updates.
- Prefer normal queued jobs with retry/backoff over long-running jobs that poll or sleep.
- Use appropriate queue names (configured in `config/horizon.php`).

## Controllers

- Use `Spatie\RouteAttributes` for route definitions: `#[Get]`, `#[Post]`, `#[Put]`, `#[Delete]`, `#[Prefix]`, `#[Middleware]`.
- Always name routes: `#[Get('/', name: 'servers.index')]`.
- Use `$this->authorize()` for policy checks.
- Return `Inertia::render()` for web, `JsonResource` for API.

## Providers (Server, DNS, Storage, SourceControl)

- Follow the Interface + AbstractProvider pattern.
- New providers extend the abstract class (e.g., `AbstractServerProvider`, `AbstractDNSProvider`).
- Implement `static id(): string` for identification.
- Registration happens via plugin classes in `app/Plugins/`.

## Services

- Extend `AbstractService` and implement `ServiceInterface`.
- Implement `static id(): string` and `static type(): string`.
- SSH scripts for services live in `resources/views/ssh/services/[service-name]/`.

## Migrations

- Migrations may modify database schema and transform existing data — but never dispatch jobs, run SSH commands, or trigger server-side actions.
- Data transformations (updating rows, backfilling columns) are fine inside migrations.
- Never trigger external side effects (SSH, queue jobs, HTTP calls, broadcasting) from migrations.

## Broadcasting

- Use `SocketEvent::dispatch()` with a `SocketEventDTO` for real-time updates.
- DTO requires `projectId`, `type` (e.g., `'server.updated'`), and `data` (typically an API Resource).

## Bootstrap Context (`GetBootstrap`)

Static-ish data shared with the frontend (provider catalogues, site types, GitHub App install state, public SSH key text, etc.) is served from `App\Actions\Bootstrap\GetBootstrap` via the `/bootstrap` endpoint and cached client-side in the Zustand `useBootstrapStore` / `useConfigs()`. It is **not** re-sent on every Inertia request — the frontend only refetches when `bootstrap_version` (md5 of the payload) changes.

Rules:

- Add new shared catalogue data to `GetBootstrap::configs()`, not to `HandleInertiaRequests::share()`.
- Only put values here if they are stable across most requests. Per-request, per-user, or rapidly-changing data belongs in Inertia props.
- If a value in `configs()` depends on DB or runtime state (e.g. `GithubApp::query()->exists()`), **every Action that mutates that state must call `GetBootstrap::forgetVersion()` after the mutation** — otherwise the cached version key won't change in production and clients will keep stale data forever. See `App\Actions\GithubApp\CreateGithubAppFromManifest` / `RemoveGithubApp` and the Plugin lifecycle Actions (`InstallPlugin`, `EnablePlugin`, etc.) for the pattern.
- Keep the matching TypeScript shape in `resources/js/types/index.d.ts` (`Configs` interface) in sync with what `configs()` returns.

## PHPDoc

- Document `@throws` annotations accurately — list what the method can actually throw.
- Keep return type annotations in sync with the actual return type.
