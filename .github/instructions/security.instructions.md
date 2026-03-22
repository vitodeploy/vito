---
applyTo: "**/*.php,**/*.ts,**/*.tsx,**/*.blade.php"
description: "Security risks to flag: auth, sensitive data, SSH safety, data integrity"
---

# Security Guidelines

## Authentication & Authorization

- Every endpoint (web and API) must be protected by appropriate middleware and Policies.
- Use `HasRolePolicies` trait in policies — it enforces role-based access (read/write/owner) against the project.
- Never ship test-only routes or UI actions to production. Gate them behind environment checks or remove them before merging.
- API keys are project-scoped — enforce project boundaries in queries and policies.

## Sensitive Data

- Never log secrets, tokens, or credentials at any log level. If you must log for debugging, use a hash or redacted form.
- Never expose internal filesystem paths (e.g., CSR/private key paths) in API responses or OpenAPI schemas.
- API Resources must explicitly select which fields to expose — never return raw model attributes.
- Use `'encrypted:json'` cast on model properties that store credentials or secrets.
- Credentials handling: when editing provider credentials, merge updates server-side. Never send existing secrets to the frontend for round-tripping.

## SSH & Server Commands

- All SSH operations go through `app/Helpers/SSH.php` and the `SSH` facade — never use `exec()`, `shell_exec()`, or `Process` directly.
- Validate and sanitize any user input that becomes part of an SSH command or Blade SSH template.
- When running commands as a different user, ensure the `cd`/`sudo` runs as the target user, not the login user.
- SSH scripts for remote servers are stored as Blade templates in `resources/views/ssh/`.
- Use `SSH::fake()` in tests — never make real SSH connections.

## Data Integrity

- Wrap multi-step mutations in database transactions. Especially critical for operations that delete-then-recreate (e.g., syncing DNS records) — a failure mid-loop must not leave partial state.
- Null-check nullable relationships before dereferencing. A `nullOnDelete()` foreign key means the relation can be null at runtime — always handle this.

## Input Validation

- Validate at the Action layer using `Validator::make()`.
- Return 422 (validation exception) for invalid input — never 400.
- Ensure validation rules match the actual data contract. For example, if `priority` only applies to MX records, make the rule conditional rather than allowing it universally.
- PHPDoc `@return` and `@throws` annotations must reflect reality — misleading annotations hide bugs.

## OpenAPI Documentation

- Keep `public/api-docs/openapi/` schemas in sync with API Resources and backend enums.
- Don't document fields that the API intentionally omits from responses.
