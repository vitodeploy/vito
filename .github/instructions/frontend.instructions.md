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

## Dialogs (centralized registry)

App-level dialogs are **not** mounted inline next to their trigger. They live in a central registry and are opened imperatively. This is the required pattern for any new modal/sheet — do not hand-roll local `open` state with a `<DialogTrigger>`.

**The pieces:**
- `resources/js/components/dialogs/registry.ts` — maps a typed key to a dialog component.
- `resources/js/hooks/use-dialog.ts` — `useDialog()` returns `dialog.<key>.open(props)` / `.close()` with full prop typing.
- `resources/js/stores/dialog-store.ts` — Zustand store holding the single active dialog.
- `resources/js/components/dialogs/dialog-host.tsx` — renders the active dialog once, app-wide.

**Opening a dialog:**
```tsx
const dialog = useDialog();
// inside a handler / DropdownMenuItem onSelect:
dialog.firewallForm.open({ serverId: server.id, firewallRule });
```

**Simple confirm / destructive actions — don't create a component, use `dialog.confirm`:**
```tsx
dialog.confirm.open({
  title: `Delete rule [${rule.name}]`,
  description: 'Are you sure? This cannot be undone.',
  variant: 'destructive',
  confirmLabel: 'Delete',
  method: 'delete',
  url: route('firewall.destroy', { server: rule.server_id, firewallRule: rule }),
});
```

**Opening from a dropdown — this is the whole point of the pattern:** use a plain `DropdownMenuItem` with the default `onSelect` so the menu closes, then open the dialog. **Never** wrap a `<Dialog>`/`<DialogTrigger>` inside a `DropdownMenuItem` with `onSelect={(e) => e.preventDefault()}` — that leaves the dropdown stuck open behind the dialog.
```tsx
<DropdownMenuItem onSelect={() => dialog.editHostedDomain.open({ hostedDomain })}>Edit</DropdownMenuItem>
```

**Authoring a registered dialog component** — it takes control props, renders `<Dialog>` directly (NO `DialogTrigger`, NO local `open` state), and suppresses Radix close-autofocus (the store restores focus):
```tsx
export default function FirewallRuleForm({
  open,
  onOpenChange,
  serverId,
  firewallRule,
}: {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  serverId: number;
  firewallRule?: FirewallRule;
}) {
  const form = useForm({ /* seed from props */ });
  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(route('firewall.store', { server: serverId }), { onSuccess: () => onOpenChange(false) });
  };
  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent onCloseAutoFocus={(e) => e.preventDefault()}>
        {/* ... */}
        <Form id="firewall-rule-form" onSubmit={submit}>{/* fields */}</Form>
        <Button form="firewall-rule-form" type="submit" disabled={form.processing}>Save</Button>
      </DialogContent>
    </Dialog>
  );
}
```
Then register it once in `registry.ts` (`firewallForm: FirewallRuleForm`). The consumer immediately gets `dialog.firewallForm.open(props)` with typed props.

**Where the dialog component file lives:** a dialog belongs to the module it serves — author it in that module's `components/` folder (e.g. the SSL activation dialog is `pages/server-ssls/components/activate-dialog.tsx`, the PHP ini dialog is `pages/php/components/ini-dialog.tsx`), and import it into `registry.ts` via its `@/pages/...` path. The folder already namespaces the file, so keep the filename short (`activate-dialog`, not `activate-server-ssl-dialog`). Only genuinely cross-cutting, generic dialogs (`confirmation-dialog`, `log-viewer-dialog`) and the registry infrastructure itself (`registry.ts`, `dialog-host.tsx`) live under `resources/js/components/dialogs/`.

**Rules & gotchas:**
- **Form submit buttons** use `form="<form-id>" type="submit"` and the `onSubmit` handler calls `e.preventDefault()`. Do not wire submit via the button's `onClick` (a bare `onClick={submit}` with no `preventDefault` lets Enter fire a native form submission alongside the Inertia request).
- **Always call `onOpenChange(false)` in `onSuccess`** to close after a successful request.
- **Lifecycle:** `DialogHost` renders the component with `open` hard-coded `true` and **unmounts it on close** (it never re-renders with `open=false`). Each open is a fresh instance (keyed by `instanceId`), so `useForm` state resets automatically — don't add manual reset-on-open effects. But any `useEffect` that pushes state *outward* based on `open` (e.g. `useInputFocus`'s `setFocused`) **must return a cleanup**, because the component unmounts while `open` is still `true`:
  ```tsx
  useEffect(() => {
    setFocused(open);
    return () => setFocused(false); // required — unmount happens with open===true
  }, [open, setFocused]);
  ```
- **Authorization:** the registry carries no authz. Every dialog's props must come from server-authorised sources (Inertia page props, API resources) — never URL params or other user-controlled input.

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
