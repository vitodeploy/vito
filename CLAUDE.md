# Project Guidelines

## Core Rules (High Priority)

- Write LESS code. Reuse existing code. No redundant code.
- Analyse codebase before adding new code. Check sibling files for conventions.
- Never run `npm build/dev` or `artisan serve` — user runs them.
- Don't run formatters (pint/prettier) — user runs them later.
- **Authoritative standards live in `.github/copilot-instructions.md` and `.github/instructions/*.md`** — read those before writing code in an unfamiliar area.
- Run `/review-pr` before declaring work done.

## Stack

Laravel 13 (L10 structure), PHP 8.4, Inertia v2, React 19, Tailwind v4, Pest 5 / PHPUnit 13

## Laravel Boost MCP Tools

- `search-docs`: Use FIRST for Laravel ecosystem docs (version-aware). Pass multiple broad queries.
- `list-artisan-commands`: Check before running artisan commands.
- `tinker`: Debug PHP/Eloquent. `database-query`: Read-only DB queries.
- `database-schema`: Inspect tables/columns before writing migrations or queries.
- `get-absolute-url`: For sharing project URLs.
- `browser-logs`: Recent browser errors/exceptions.

## PHP

- Curly braces always. Constructor property promotion. Explicit return types on all methods.
- Avoid commenting unless it is necessary and only on Doc Blocks. no inline commenting.
- Use array shapes in PHPDoc where appropriate.
- Avoid `DB::`, prefer `Model::query()`. Eager load to prevent N+1.
- Use `config()` not `env()` outside config files.

## Laravel — Architecture

Vito has a specific architecture. Match these patterns exactly:

- **Models** extend `app/Models/AbstractModel.php`, NOT `Illuminate\Database\Eloquent\Model`. `AbstractModel` includes `HasTimezoneTimestamps` and `jsonUpdate()`.
- **Enums** implement `app/Contracts/VitoEnum.php` — requires `getColor(): string` and `getText(): string`. Cast enum properties to their enum class on the model.
- **Actions** in `app/Actions/` hold ALL business logic. Methods named `create()` / `update()` / `handle()` / `run()` / `install()` etc. Validate inside the Action with `Validator::make()`; extract to a private `validate()` when rules are complex. Compose Actions instead of building monoliths.
- **Controllers** are thin: receive request → call Action → return response. Use `Spatie\RouteAttributes` (`#[Get]`, `#[Post]`, `#[Prefix]`, `#[Middleware]`) and ALWAYS name routes (`#[Get('/', name: 'servers.index')]`). Return `Inertia::render()` for web, `JsonResource` for API. Call `$this->authorize()` for policy checks.
- **Policies** in `app/Policies/` use the `HasRolePolicies` trait — provides `hasReadAccess()`, `hasWriteAccess()`, `hasOwnerAccess()`. No inline permission checks anywhere.
- **Jobs** implement `ShouldQueue` with both `Queueable` AND `UniqueQueue` traits. Wrap work in `$this->run($key, callable)`. Implement `failed(Exception $e)` for status updates. Prefer queued jobs over long-running pollers.
- **API Resources** in `app/Http/Resources/`. Explicitly whitelist fields — never `parent::toArray()`. ALWAYS call `->getText()` / `->getColor()` on enum properties — never return raw enum values.
- **Broadcasting** uses `SocketEvent::dispatch()` with a `SocketEventDTO` (`projectId`, `type` like `'server.updated'`, `data` typically an API Resource).
- **Bootstrap context**: shared catalogue data (provider lists, site types, GitHub App install state, public key text) is served from `App\Actions\Bootstrap\GetBootstrap` via `/bootstrap` and cached client-side via `useConfigs()`. Add new catalogue values to `GetBootstrap::configs()`, NOT to `HandleInertiaRequests::share()`. **Any Action that mutates DB/runtime state reflected in `configs()` must call `GetBootstrap::forgetVersion()` after the mutation** — otherwise the cached `bootstrap_version` never changes and clients see stale data forever. Keep `resources/js/types/index.d.ts` `Configs` in sync.
- **Providers** (Server/DNS/Storage/SourceControl): Interface + AbstractProvider pattern. Extend the abstract (e.g. `AbstractServerProvider`), implement `static id(): string`. Register via plugin classes in `app/Plugins/`.
- **Services** (database/webserver/PHP/etc.): extend `AbstractService`, implement `ServiceInterface`. Implement `static id()` and `static type()`. SSH scripts live in `resources/views/ssh/services/[service-name]/`.

## Laravel — Workflow

- Use `php artisan make:*` with `--no-interaction` for new files.
- **Don't use FormRequests.** All validation lives in Actions via `Validator::make()`.
- Return **422 (validation exception) for invalid input** — never 400.
- Wrap multi-step mutations in `DB::transaction()`. Critical for delete-then-recreate flows (e.g. DNS record sync).
- Null-check nullable relationships — `nullOnDelete()` foreign keys can be null at runtime.
- Let provider/service errors **bubble up**; don't silently swallow exceptions.
- Use `'encrypted:json'` cast for credentials and secrets on models.
- API keys are **project-scoped** — enforce the project boundary in queries and policies.

## Migrations

- Schema changes and data transforms only. **Never** dispatch jobs, run SSH, make HTTP calls, or broadcast events from migrations.
- Combine related schema changes into a single migration file per feature.
- Never run `migrate:fresh` or other destructive DB commands — use tests to verify migrations.

## Frontend

- Inertia pages in `resources/js/pages/`. React components in `resources/js/components/`. Functional components + hooks.
- Forms: use the `useForm` helper, follow existing patterns. Navigation: `<Link>` or `router.visit()` — never raw `<a>` for internal routes.
- **Dialogs**: all modals/sheets use the centralized registry (`resources/js/components/dialogs/registry.ts`) opened via `useDialog()` — `dialog.<key>.open(props)`, or `dialog.confirm.open({...})` for simple confirms. Never nest `<Dialog>`/`<DialogTrigger>` inside a `DropdownMenuItem`. **Read the "Dialogs" section of `.github/instructions/frontend.instructions.md` before adding or changing any dialog.**
- Tailwind v4: `@import "tailwindcss"`, `@theme` for config, **`gap-*` over margins** for sibling spacing.
- Use Shadcn components and semantic tokens (`text-foreground`, `bg-background`, `text-muted-foreground`). Avoid hard-coded colors and custom CSS.
- **React hooks**: include ALL dependencies in `useEffect` / `useMemo` / `useCallback` arrays. Return cleanup for timers/subscriptions. Stale closures from missing deps are a recurring bug here.
- **Dynamic forms**: backend ships `DynamicField` / `DynamicForm` DTOs (types: text, password, password-with-toggle, textarea, select, checkbox, alert, component). Render based on `field.type`. Don't hardcode provider-specific fields. Respect the field's value type — don't force everything to string.
- **Accessibility**: `<button>` for clickable elements, not `<span onClick>`. Add ARIA attributes when semantic HTML isn't enough.
- **TypeScript types**: keep `resources/js/types/*.d.ts` in sync with backend API Resources. Enum status fields arrive as `{ status: string, status_color: string }`.
- Handle promise rejections from browser APIs (`navigator.clipboard.writeText`, etc.) and show error feedback.

## Testing

- Pest 5 only. Create with `php artisan make:test --pest`.
- `TestCase` provides `$this->user`, `$this->server`, `$this->site` pre-configured.
- Test logic only, not framework. Use factories. RefreshDatabase trait.
- Add to existing test files when possible. Run minimal tests with `--filter`.
- Don't remove tests without approval.
- Feature tests for user flows, Unit tests for isolated logic.
- Use `SSH::fake()` for SSH operations and `Http::fake()` for HTTP — never make real connections.
- Use `assertDatabaseHas()` for DB assertions.

## Architecture Tests

- `tests/Arch/` enforces the rules in this file as executable Pest tests. **A failing architecture rule means the code is wrong — fix the code, don't exempt it.**
- Every exception lives in ONE registry: `ArchTestCase::EXCEPTIONS` in `tests/ArchTestCase.php`, keyed and referenced via `ArchTestCase::except('key')`.
- **NEVER add an entry to `EXCEPTIONS` without the user's explicit permission.** The only acceptable case is when you are adding or changing a rule and existing code cannot be brought into line in that same change.
- Before asking permission, tell the user: which rule stops applying and to which classes, what that rule was protecting against, that the exemption is permanent until deliberately removed, and what fixing the code instead would cost. A general "sounds good" on surrounding work is not permission.
- Removing entries after fixing the code needs no permission — lists may shrink freely, never grow.
- **Read `.github/instructions/architecture-tests.instructions.md` before adding or changing any architecture rule.** It covers the Pest gotcha where negative dependency rules silently pass when given an array of source namespaces, and the requirement to prove a new rule can actually fail.

## API

- Consider API endpoints for new features but ask user first.
- Use API Resources for consistent responses.
- Document endpoints in `public/api-docs/openapi`. Keep schemas in sync with API Resources and backend enums. Don't document fields the API intentionally omits.

## Security

- Every web/API endpoint must be protected by appropriate middleware AND a Policy.
- Never log secrets, tokens, or credentials at any log level. Redact or hash if debugging.
- Never expose internal filesystem paths (CSR/private key paths, etc.) in API responses or OpenAPI.
- When editing credentials, merge updates server-side. Never round-trip existing secrets through the frontend.
- No test-only routes or UI actions in production. Env-gate or remove before merging.

## SSH

- All SSH goes through `app/Helpers/SSH.php` and the `SSH` facade. **No `exec()`, `shell_exec()`, `system()`, `passthru()`, or `Symfony\Component\Process\Process`.**
- SSH scripts for remote servers are Blade templates in `resources/views/ssh/`.
- Validate and sanitize any user input that lands in an SSH command or template.
- When running as a different user, `cd`/`sudo` must run as the target user, not the login user.

## Code Review

- `/review-pr [target-branch]` runs the three project reviewer agents (`php-laravel-reviewer`, `frontend-reviewer`, `security-reviewer`) against the current branch's diff and returns prioritised findings.
- Auto-detects PR target via `gh` if a PR is open; otherwise prompts for the target branch.

## Git

- Use `gh` CLI for issues/PRs.
- Don't change dependencies or create new base folders without approval.
