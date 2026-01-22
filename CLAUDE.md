# Project Guidelines

## Core Rules (High Priority)

- Write LESS code. Reuse existing code. No redundant code.
- Analyse codebase before adding new code. Check sibling files for conventions.
- Never run `npm build/dev` or `artisan serve` - user runs them.
- Don't run formatters (pint/prettier) - user runs them later.

## Stack

Laravel 12 (L10 structure), PHP 8.4, Inertia v2, React 19, Tailwind v4, PHPUnit 11

## Laravel Boost MCP Tools

- `search-docs`: Use FIRST for Laravel ecosystem docs (version-aware). Pass multiple broad queries.
- `list-artisan-commands`: Check before running artisan commands.
- `tinker`: Debug PHP/Eloquent. `database-query`: Read-only DB queries.
- `get-absolute-url`: For sharing project URLs.
- `browser-logs`: Recent browser errors/exceptions.

## PHP

- Curly braces always. Constructor property promotion. Explicit return types.
- PHPDoc blocks over inline comments. Array shapes where appropriate.
- Avoid `DB::`, prefer `Model::query()`. Eager load to prevent N+1.
- Use `config()` not `env()` outside config files.

## Laravel

- Use `php artisan make:*` with `--no-interaction` for new files.
- Form Request classes for validation, not inline.
- Queued jobs for slow operations (`ShouldQueue`).
- Named routes with `route()` function.
- L10 structure: Middleware in `app/Http/Kernel.php`, Providers in `app/Providers/`.
- Use Actions. Thin Controllers. follow the pattern.
- Use Policies for authorization, not inline checks.
- Don't use FormRequests. Validations in Actions.
- Use Resource classes for API responses.

## Frontend

- Inertia pages in `resources/js/pages`. Use `Inertia::render()`.
- Forms with `useForm` helper (follow existing patterns). Navigation with `<Link>` or `router.visit()`.
- Tailwind v4: Use `@import "tailwindcss"`, `@theme` for config, gap not margins.
- Use Shadcn components and shadcn patterns like `text-foreground`, `bg-background`, ...
- React components in `resources/js/components`. Use functional components and hooks.
- CSS in `resources/css`. Use Tailwind utility classes. Avoid custom CSS unless necessary.

## Testing

- PHPUnit only. Create with `php artisan make:test --phpunit`.
- Test logic only, not framework. Use factories. RefreshDatabase trait.
- Add to existing test files when possible. Run minimal tests with `--filter`.
- Don't remove tests without approval.
- Feature tests for user flows, Unit tests for isolated logic.
- Mock external services. Use `Http::fake()` for HTTP calls.
- Use `assertDatabaseHas()` for DB assertions.
- Use RefreshDatabase trait for clean state.

## API

- Consider API endpoints for new features but ask user first.
- Use API Resources for consistent responses.
- Document endpoints in `public/api-docs/openapi`

## Git

- Use `gh` CLI for issues/PRs.
- Don't change dependencies or create new base folders without approval.

## SSH

- All SSH commands are being run using app/Helpers/SSH.php
