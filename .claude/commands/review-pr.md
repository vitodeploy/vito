---
description: Comprehensive PR review against the target branch using the project's PHP/Laravel, frontend, and security reviewer agents.
argument-hint: "[target-branch]   # optional, e.g. 3.x or 4.x"
allowed-tools: Bash, Read, Grep, Glob, AskUserQuestion, Agent
---

# /review-pr — comprehensive branch/PR review

You are running a multi-agent review of the changes on the current branch against a target branch, similar to GitHub Copilot's PR review but tuned for Vito's standards.

## Step 1 — Determine the target branch

Resolve the **target branch** (the branch we will merge into) in this exact order, stopping at the first that succeeds:

1. **Explicit argument.** If the user provided one (`$1`), use it: `TARGET="$1"`.
2. **Open PR for the current branch.** If `gh` is installed and there is an open PR for the current branch, use its base ref:
   ```bash
   gh pr view --json baseRefName -q .baseRefName 2>/dev/null
   ```
   If this returns a non-empty value, use it as `TARGET`.
3. **Ask the user.** Use `AskUserQuestion` with the question "Which branch are we merging into?". Options must be:
   - `3.x` — Current stable line
   - `4.x` — Next major
   - (Users can pick "Other" to type a custom branch.)

Print the resolved target branch to the user before continuing, e.g. `Target branch: 3.x`.

## Step 2 — Build the diff

Compute the merge base and write a per-domain diff file under `/tmp/vito-review-$$/`:

```bash
mkdir -p /tmp/vito-review-$$
BASE=$(git merge-base HEAD "origin/$TARGET" 2>/dev/null || git merge-base HEAD "$TARGET")

# Changed file lists — note: php-laravel scope includes Blade SSH templates and
# OpenAPI YAML because both are part of the PHP/Laravel surface even though
# they aren't .php files.
git diff --name-only "$BASE"...HEAD -- '*.php' 'resources/views/ssh/**/*.blade.php' 'public/api-docs/openapi/**/*.yaml' > /tmp/vito-review-$$/php.files
git diff --name-only "$BASE"...HEAD -- '*.ts' '*.tsx' '*.css'           > /tmp/vito-review-$$/fe.files
git diff --name-only "$BASE"...HEAD -- '*.php' '*.ts' '*.tsx' '*.blade.php' 'public/api-docs/openapi/**/*.yaml' > /tmp/vito-review-$$/sec.files

# Diffs (capped to keep agents focused — large PRs trim oldest hunks)
git diff --no-color "$BASE"...HEAD -- '*.php' 'resources/views/ssh/**/*.blade.php' 'public/api-docs/openapi/**/*.yaml' > /tmp/vito-review-$$/php.diff
git diff --no-color "$BASE"...HEAD -- '*.ts' '*.tsx' '*.css'            > /tmp/vito-review-$$/fe.diff
git diff --no-color "$BASE"...HEAD -- '*.php' '*.ts' '*.tsx' '*.blade.php' 'public/api-docs/openapi/**/*.yaml' > /tmp/vito-review-$$/sec.diff

# Sibling context: list every directory touched by the diff so the agents
# can grep siblings within those dirs without having to discover them.
# This is the input that powers cross-sibling consistency checks.
git diff --name-only "$BASE"...HEAD | xargs -n1 dirname 2>/dev/null | sort -u > /tmp/vito-review-$$/touched.dirs
```

Print a short summary to the user: target branch, merge base SHA (first 8 chars), counts per domain, and the number of touched directories (e.g. `12 PHP files, 5 frontend files, 14 files in scope for security; 8 touched dirs`).

If **all three** file lists are empty, stop and tell the user there's nothing to review.

## Step 3 — Spawn the three reviewer agents in parallel

In a **single message** with three `Agent` tool calls, dispatch:

- `subagent_type: php-laravel-reviewer` — only if `php.files` is non-empty.
- `subagent_type: frontend-reviewer` — only if `fe.files` is non-empty.
- `subagent_type: security-reviewer` — always run if either of the above is non-empty.

Each agent prompt must include:

- The target branch and merge base SHA.
- The path to its `.files` and `.diff` files (e.g. `/tmp/vito-review-$$/php.diff`).
- The path to `/tmp/vito-review-$$/touched.dirs` — the agent is required to read **at least one untouched sibling** from each touched directory before reporting, so cross-sibling consistency checks have real reference points.
- An instruction: "Read the relevant `.github/instructions/*.md` file before reviewing. Review only the changed hunks. Output in the exact markdown format defined in your agent spec."
- An instruction to keep findings tightly scoped to changed lines, **except** for Cross-sibling / Cross-component consistency findings, which must cite the sibling file(s) they're comparing against.
- An explicit reminder: "Surface design-fragility and defense-in-depth issues at Low / Nit severity. Today-it-works isn't the bar — `Object.values(errors)[0]`, `class_basename($e)` in user-visible strings, default-arg side-effects, and internal-only fields in `$fillable` are all worth flagging even when no current caller is affected."

## Step 4 — Consolidate the findings

When all agents have returned, present a single consolidated report to the user with this structure:

```
# PR Review — <current-branch> → <target-branch>
Merge base: <sha8>   Files reviewed: <php>/<fe>/<sec>

## Summary
- Critical: N
- High:     N
- Medium:   N
- Low/Nit:  N

<verbatim PHP / Laravel Review section>

<verbatim Frontend Review section>

<verbatim Security Review section>

## Suggested next steps
- 1–3 bullet points prioritising the most impactful fixes.
```

Do not re-order or rewrite the agents' findings — paste them verbatim under their headings. The summary counts come from counting bullets per severity across all three sections.

## Step 5 — Cleanup

```bash
rm -rf /tmp/vito-review-$$
```

## Rules

- Do **not** modify any source files during the review.
- Do **not** run formatters, tests, migrations, or `npm`/`artisan` commands.
- If an agent returns "No issues found.", keep its heading and the no-issues line in the consolidated report.
- If `gh` is not installed and no target was provided, skip step 1.2 and go straight to asking.
