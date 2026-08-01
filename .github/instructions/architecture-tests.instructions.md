---
applyTo: "tests/Arch/**,tests/ArchTestCase.php"
description: "Architecture test suite and the rules governing its exception registry"
---

# Architecture Tests

`tests/Arch/` is a Pest 5 architecture suite that enforces the standards in
`.github/copilot-instructions.md` and `.github/instructions/*.md` as executable
rules. It runs as the `Arch` testsuite and is included in `php artisan test`.

## The exception registry

Every deviation the current implementation relies on lives in **one place**:
`ArchTestCase::EXCEPTIONS` in `tests/ArchTestCase.php`. Rules reference it by
key:

```php
arch('models extend AbstractModel')
    ->expect('App\Models')
    ->classes()
    ->toExtend(AbstractModel::class)
    ->ignoring(ArchTestCase::except('models.not-on-abstract-model'));
```

Each key holds a `reason` and an `entries` list. `except()` throws on an unknown
key, so typos fail loudly rather than silently exempting nothing.

## Adding to EXCEPTIONS requires explicit permission

**Never add an entry to `ArchTestCase::EXCEPTIONS` on your own initiative, and
never add one to make a failing rule pass.** A failing architecture rule means
the code is wrong, not the rule.

The only acceptable reason to add entries is when you are **adding a new rule or
changing an existing one**, and existing code cannot be brought into line within
that change. Even then, ask first.

Before asking, tell the user plainly:

- **Which rule** stops applying, and **to which classes**.
- **What that rule was protecting against**, and what can now happen unnoticed
  in those classes.
- That the entry is permanent until someone deliberately removes it, so the
  drift becomes invisible to every future reviewer and agent.
- The alternative: fixing the code so no exception is needed, and what that
  would cost.

Then wait for an explicit yes. Silence, "sounds good", or a general approval of
the surrounding work is not permission.

## When a rule fails

1. Fix the code. This is almost always the right answer.
2. If the rule is wrong — too broad, encoding a convention that was never
   agreed, or vacuous — say so and propose changing or deleting the rule.
3. Only then consider an exception, following the permission process above.

Exception lists may shrink freely. Removing an entry after fixing the code needs
no permission — that is the intended direction of travel.

## Writing rules

- **One source namespace per expectation.** Pest's negative dependency
  expectations (`not->toUse`, `not->toBeUsedIn`) **silently pass** when given an
  array of source namespaces. Arrays are fine on the destination side.
- **Verify a new rule can fail.** Add a class that violates it, confirm the rule
  goes red, then remove the class. A rule that cannot fail is worse than no rule
  — it reads as coverage.
- **Prefer tight negative rules over broad allowlists.** `not->toBeUsedIn([read
  and decision layers])` states an invariant; `toOnlyBeUsedIn([ten namespaces])`
  just records today's call graph and churns on every new file.
- **Do not encode conventions that are not in the instruction files.** If it is
  worth enforcing, document it there first.
