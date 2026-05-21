---
applyTo: "**/*.ts,**/*.tsx,**/*.css"
description: "React, Inertia, Tailwind v4, and TypeScript frontend standards"
---

# Frontend Guidelines (React / Inertia / Tailwind)

## Inertia & React

- Inertia pages live in `resources/js/pages/`. Use `Inertia::render()` from Laravel controllers.
- React components in `resources/js/components/`. Use functional components and hooks.
- Use the `useForm` helper for forms — follow existing patterns in the codebase.
- Use `<Link>` or `router.visit()` for navigation — never raw `<a>` tags for internal routes.

## Bootstrap Context (`useConfigs`)

- Shared catalogue data (provider lists, site types, GitHub App install state, public key text) is **not** in `page.props` — it lives in the Zustand `useBootstrapStore`, exposed via `useConfigs()` and `usePublicKeyText()` from `@/stores/bootstrap-store`.
- Read these with `const configs = useConfigs()!;` inside any component rendered under the app layout. The `!` is safe because the layout gates on `configs !== null`.
- Never reach for `page.props.configs` / `page.props.public_key_text` — those keys do not exist. If you see them in old PRs or copy-pasted code, migrate to `useConfigs()`.
- The `Configs` shape lives in `resources/js/types/index.d.ts`. Keep it in sync with the backend `GetBootstrap::configs()` return shape.

## Tailwind v4

- Use `@import "tailwindcss"` and `@theme` for configuration.
- Prefer `gap` utilities over margins for spacing between siblings.
- Use Shadcn component patterns and semantic tokens: `text-foreground`, `bg-background`, `text-muted-foreground`, etc.
- Avoid custom CSS unless absolutely necessary — prefer Tailwind utility classes.

## Dynamic Forms

- The backend provides `DynamicField` and `DynamicForm` DTOs for provider-specific forms.
- `DynamicField` supports types: text, password, password-with-toggle, textarea, select, checkbox, alert, component.
- Render these dynamically based on the field type — don't hardcode provider-specific fields in the UI.
- Respect the field's value type (string/number/boolean/string[]) — don't force-cast everything to string.

## React Hooks

- Always include all dependencies in `useEffect` / `useMemo` / `useCallback` dependency arrays. Stale closures from missing deps are a recurring issue.
- Clean up timers/subscriptions on unmount — return a cleanup function from `useEffect`.

## Accessibility

- Interactive elements must be keyboard-accessible. Use `<button>` for clickable elements, not `<span onClick>`.
- Add appropriate ARIA attributes when semantic HTML isn't sufficient.

## TypeScript Types

- Keep TypeScript type definitions in `resources/js/types/` in sync with backend API Resources.
- When adding new fields to API Resources, update the corresponding `.d.ts` file.
- Enum status fields come as `{ status: string, status_color: string }` from the backend (via `getText()` / `getColor()`).

## Clipboard & Async

- Handle promise rejections from browser APIs (e.g., `navigator.clipboard.writeText()`).
- Show error feedback when async browser operations fail.
