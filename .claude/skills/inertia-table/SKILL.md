---
name: inertia-table
description: Build, modify, or migrate data tables in Vito4 using forjedio/inertia-table (backend-driven Laravel + Inertia + React tables). TRIGGER when - creating or editing files under app/Tables/; adding a new index/listing page; rendering paginated lists, sortable columns, searchable tables, or row actions; touching controllers/pages that pass paginators to Inertia; the user mentions "table", "listing", "data grid", "paginate", "InertiaTable", or migrating away from the legacy `@/components/data-table` (DataTable). SKIP for pure UI tables with hardcoded rows (no backend query).
---

# forjedio/inertia-table — Vito4 skill

Backend-driven dynamic tables: define columns, sorting, search and pagination once in a PHP `Table` class; render anywhere with `<InertiaTable />`. The PHP class is the source of truth — never duplicate column config on the frontend.

## When to use this vs the legacy `DataTable`

Vito4 has a legacy `resources/js/components/data-table.tsx` (custom TanStack wrapper). For **new** tables, use `InertiaTable`. When asked to modify an existing legacy table:
- If the change is small/cosmetic, stay in `DataTable`.
- If the user is migrating, refactoring, or adding non-trivial behavior (sort, search, badges, enums), propose moving to `InertiaTable` and reference this skill.

The `App\Vito\Plugins\Forjedio\EnumColumns\Plugin` is already booted — every `EnumColumn` automatically gets `->ucFirst()` applied globally.

## Quick reference

### 1. Generate the Table class

```bash
php artisan make:table ServerTable --model=Server
# nested: php artisan make:table Servers/SiteTable --model=Site
```

Creates `app/Tables/ServerTable.php` (path is set by `config('inertia-table.table_path', 'Tables')`).

### 2. Define columns and (optionally) searchable fields

```php
namespace App\Tables;

use App\Models\Server;
use Forjed\InertiaTable\Column;
use Forjed\InertiaTable\Columns\{TextColumn, BadgeColumn, EnumColumn, DateTimeColumn, ActionsColumn};
use Forjed\InertiaTable\Table;

class ServerTable extends Table
{
    protected string $defaultSort = '-created_at'; // leading "-" = desc
    protected int $perPage = 15;

    protected function query(): void
    {
        // Default scopes / eager loads applied to every query
        $this->query->with('provider');
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('name', 'Name')->sortable(),
            TextColumn::make('provider.name', 'Provider')->sortable(),  // dot notation -> relation column
            EnumColumn::make('status', 'Status'),                       // requires enum implements HasTableDisplay
            BadgeColumn::make('os', 'OS')->variant('secondary'),
            DateTimeColumn::make('created_at', 'Created')->sortable()->toLocal(),
            ActionsColumn::make(),                                      // per-row action buttons (frontend)
            Column::data('id'),                                          // hidden data, available to actions/cell renderers
        ];
    }

    protected function searchable(): array
    {
        return ['name', 'ip', 'provider.name'];
    }
}
```

### 3. Use the table in the controller

```php
return Inertia::render('servers/index', [
    'servers' => ServerTable::make(Server::query())->paginate(),
    // ->simplePaginate()  // no total count, lighter query
    // ->toArray($take, $skip)  // raw rows only (no pagination wrapper)
]);
```

Multiple tables on one page → give each an identifier so URL params don't collide:

```php
'servers' => ServerTable::make(Server::query())->identifier('servers')->paginate(),
'sites'   => SiteTable::make(Site::query())->identifier('sites')->paginate(),
// URL params become serversSearch / serversSort / serversPage etc.
```

### 4. Render in React (`resources/js/pages/...`)

```tsx
import { InertiaTable } from '@forjedio/inertia-table-react';
import type { InertiaTableData, Row } from '@forjedio/inertia-table-react';
import { Link } from '@inertiajs/react';
import { Pencil, Trash2 } from 'lucide-react';

export default function Index({ servers }: { servers: InertiaTableData }) {
    return (
        <InertiaTable
            tableData={servers}
            actions={(row) => (
                <>
                    <Link href={route('servers.show', row.id as number)}><Pencil className="size-4" /></Link>
                    <button onClick={() => destroy(row.id)}><Trash2 className="size-4" /></button>
                </>
            )}
        />
    );
}
```

That's it — search, sort, pagination work automatically via Inertia `router` reloads.

## Column types — cheat sheet

| Class | Purpose | Notable methods |
|---|---|---|
| `Column::make($name, $header)` | Generic column | All fluent methods (sortable, hidden, fit, value, adjust, badge, date, link, etc.) |
| `Column::data($name, ?$closure)` | Hidden data row (no display, accessible to `actions`/cell renderers) | |
| `TextColumn` | Plain text | inherits all |
| `BadgeColumn` | Coloured pill | `->variant('success')`, `->colorField('status_color')` |
| `EnumColumn` | PHP enum → text + badge colour | enum must implement `Forjed\InertiaTable\Contracts\HasTableDisplay` |
| `BooleanColumn` | Yes/No (or custom) | `->yesText('Active')->noText('Inactive')` |
| `DateColumn` | Formatted date | `->format('Y-m-d')`, `->toLocal()` |
| `DateTimeColumn` | Date + time | `->toLocal()` (sets `includeTime`) |
| `LinkColumn` | Anchor cell | `->route('servers.show', ['server' => ':id'])` (`:name` = pull from row) |
| `CopyableColumn` | Value with copy-to-clipboard button | |
| `ComponentColumn::create($name, $header, $componentName)` | Custom React cell | must `registerCellComponent($componentName, Comp)` on frontend |
| `ActionsColumn::make()` | Renders the `actions` render-prop | `fit` is on by default |

### Fluent modifiers (work on every column)

```php
TextColumn::make('email', 'Email')
    ->sortable()
    ->hidden()                       // not rendered, but row data is still sent
    ->fit()                          // shrink-to-content width
    ->fallback('N/A')                // when value is null
    ->uppercase() / ->lowercase() / ->ucFirst() / ->ucWords()
    ->value(fn ($model) => $model->user->email)  // custom value resolver
    ->adjust(fn ($v) => strtoupper($v))          // chain transforms
    ->accessor('users.email')                    // sort/search target differs from name
    ->badge('status', colorField: 'status_color', tooltip: fn ($m) => $m->note)
    ->withIcon(['active' => 'check', 'down' => 'x'], default: 'circle')
    ->asIcon(fn ($m) => $m->is_pro ? 'star' : 'circle');  // icon-only column
```

Icons resolve via `lucide-react` by default; register custom ones with `registerIcon(name, Component)` / `registerIcons({...})` from `inertia-table-react`.

## Enums (very common in Vito4)

```php
enum ServerStatus: string implements \Forjed\InertiaTable\Contracts\HasTableDisplay
{
    case Ready = 'ready';
    case Installing = 'installing';
    case Failed = 'failed';

    public function getText(): string  { return ucfirst($this->value); }
    public function getColor(): string { return match ($this) {
        self::Ready => 'success',
        self::Installing => 'warning',
        self::Failed => 'danger',
    }; }
}
```

Then: `EnumColumn::make('status', 'Status')` — the global plugin already applies `ucFirst`, so simple `lowercase` enum values render capitalised.

## Custom cell components

```php
// PHP
ComponentColumn::create('health', 'Health', 'ServerHealthBadge')
```

```tsx
// resources/js/app.tsx (or a dedicated bootstrap)
import { registerCellComponent } from '@forjedio/inertia-table-react';
import { ServerHealthBadge } from '@/components/server-health-badge';
registerCellComponent('ServerHealthBadge', ServerHealthBadge);
```

The component receives `{ row, value, column }`.

## Sorting, search, pagination — what you get for free

- **Sort:** click sortable header → URL gets `?sort=name` or `?sort=-name`. PHP priority: user sort > pre-existing `orderBy` on the builder > `$defaultSort`.
- **Search:** debounced input (default 300 ms, see `config/inertia-table.php`). Only active when `searchable()` returns fields. Uses `LIKE %term%` with OR across fields.
- **Pagination:** `paginate()` = full (with total/last_page); `simplePaginate()` = lighter.
- All params can be scoped per table via `->identifier('servers')`.

## Hooks — modify queries / data without subclassing

Class-specific (best in a `ServiceProvider::boot`):

```php
ServerTable::beforeQuery(ServerTable::class, function ($query, array &$columns) {
    $query->where('team_id', auth()->user()->current_team_id);
});

ServerTable::afterData(ServerTable::class, function (\Illuminate\Support\Collection $rows) {
    return $rows->map(fn ($r) => [...$r, 'computed' => /* ... */]);
});
```

Global hooks (`Table::globalBeforeQuery`, `Table::globalAfterData`) already exist in Vito4 via the EnumColumns plugin — follow that pattern when adding new cross-cutting behaviour.

## Common patterns

**Hide soft-deletes / restricted rows:** put the scope in `query()`.

**Cross-relation search:** add the relation column with dot-notation, then put `'provider.name'` in `searchable()`. The trait builds the join-aware `LIKE` clauses for you.

**Settings → frontend hooks:** `->withSettings(['polling' => 5])` ships arbitrary data; on the React side register a hook with `registerTableHook('polling', ({ value, refresh }) => { const id = setInterval(refresh, value*1000); return () => clearInterval(id); })`.

**Row click to detail page:** `<InertiaTable onRowClick={(row) => router.visit(route('servers.show', row.id as number))} />`. The component already skips clicks on `a, button, input, select, textarea, [role="button"]`.

**External search input** (e.g. in a page header instead of the toolbar): pass `searchRef={inputRef}` — the built-in search bar hides and the table watches that input.

## Gotchas

1. **Every row needs an `id`.** Add `Column::data('id')` if `id` isn't otherwise included.
2. **Dot-notation columns get a `_`-prefixed name** in the row payload (e.g. `provider.name` → row key `_provider_name`). Use the column's display value; don't reach into the row by the dotted path from the frontend.
3. **Sort key vs display name:** for computed columns, set `->accessor('real_db_column')` or sort will target the wrong field.
4. **Ziggy** is a peer dep — Vito4 uses it, so leave `use_ziggy => true`. If you ever turn it off, link columns send pre-resolved URLs.
5. **TypeScript prop type** for an Inertia page is `InertiaTableData`, not the row type. Cast `row.id` (it's `string | number`).
6. **`ActionsColumn`** does nothing on its own — you must pass `actions={(row) => ...}` to `<InertiaTable />`.
7. **`registerCellComponent` / `registerIcon` must run before render.** Put them in `app.tsx` (or a module imported there), not inside the page component.
8. **`@inertiajs/react` v2** is required (Vito4 has it). Pagination/search use `router.get` with `preserveState: true` semantics under the hood.
9. **`make:table` won't overwrite** an existing file — delete it first if you're regenerating.
10. **Don't mock the DB in tests** ([[feedback_no_migrate_fresh]] applies); write feature tests that hit a real test DB and assert the Inertia prop has the expected `columns`/`data`/`meta` shape.

## Verifying changes

```bash
php artisan test --filter=ServerTableTest        # if you add a feature test
npm run build                                    # tsc + vite catches type errors in pages
```

For UI verification, start the dev server and exercise sort/search/pagination in the browser — type-checking confirms the prop shape, not the rendered behaviour.

## File map

- PHP package: `vendor/forjedio/inertia-table/src/` (`Table.php`, `Column.php`, `Columns/*`, `Concerns/*`)
- React package: `vendor/forjedio/inertia-table/react/src/` (importable as `inertia-table-react`)
- Config: publish via `php artisan vendor:publish --tag=inertia-table-config` → `config/inertia-table.php`
- Existing plugin: `app/Vito/Plugins/Forjedio/EnumColumns/Plugin.php` (template for global hooks)
- Stub used by `make:table`: `vendor/forjedio/inertia-table/stubs/table.stub`
- Upstream docs: <https://inertia-table.forjed.io/> (and `/llms.txt`)
