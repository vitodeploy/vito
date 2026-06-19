# Breaking Changes

## Custom VHost Configuration

In v4.x the vhost template engine changed from **Blade** to
[**Mustache**](https://mustache.github.io). If you have customised the default vhost template, your
existing customisations will not carry over, and any Blade syntax (`@if`, `{{ $variable }}`, etc.)
will no longer be interpreted. See the [Mustache manual](https://mustache.github.io/mustache.5.html)
for the new syntax.

:::warning
To avoid breaking live sites during the upgrade, vhost generation is **disabled by default on every
existing site**. Each site needs you to review and enable the new template before Vito will
regenerate its vhost.
:::

For each site, open its **Settings** page to see the current vhost alongside a **preview** of the
new Mustache-generated output. Once you are happy that the new template produces the configuration
you expect, enable it to switch the site over.

## NodeJs Sites

The original **NodeJs** site type is now **deprecated**. Existing NodeJs sites will continue to work,
but the type can no longer be used to create new sites.

Use the new **Node** site type instead. It uses [Site Tooling](/docs/4.x/sites/site-tooling) to
manage Node versions and any other tools your site needs, rather than baking the version into the
site type.

There is no automated migration from the deprecated type to the new one. If you want the new
features on an existing NodeJs site, you will need to migrate it manually.

## VitoAgent updated

The `vitoagent` service has been updated for v4.x, and if you are running this, you will need to update it
via the services for each server.  You can do this easily by uninstalling and reinstalling the VitoAgent on each
server it's currently installed on. 

:::info
Where a VitoAgent is not updated on a given server, certain monitoring statistics will appear empty of have no value
and restart monitoring of the server will not function.
:::

## Plugins

If you maintain a Vito plugin, these are the contract changes you need to be aware of in v4.x. The
plugin registration API (`App\Plugins\Register*` builders, `AbstractPlugin`, `PluginInterface`) and
the core `ServiceInterface` / `AbstractService` base classes are **unchanged**. Every breaking
change below lives in the contracts that specific provider types extend.

:::info
**Who this affects:** plugins registering a **Site Type**, **Web Server** service, or **Source
Control** provider. Plugins that only register Server providers, Storage providers, DNS providers,
Notification channels, Workflow actions, Server/Site features, or Commands are unaffected.
:::

### At a glance

| Your plugin registers...                                       | Action needed                                                                                                     |
| -------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------- |
| Site type                                                      | **Yes** - move `vhost()` to `vhostTemplate()` + `vhostData()`; fix the `progress()` signature                     |
| Web server                                                     | **Yes** - implement `generateVhost()` + `deploySplash()`; update `updateVHost()`; drop `changePHPVersion()`       |
| Source control                                                 | Only if you implement the interface directly (not via the abstract)                                               |
| Anything reading `$site->user` / `$site->ssh_key` / FPM pools  | **Review** - these are now isolated-user-backed                                                                   |
| Server provider, Storage, DNS, Notifications, Workflow, Features, Commands | None                                                                                                  |

### Site Types

The `App\SiteTypes\SiteType` interface was reworked. The vhost rendering model changed from "return
markup" to "point at a template and supply data".

**Removed**

```php
// GONE from the SiteType interface
public function vhost(string $webserver): string|View;
```

**Added** (required on the interface):

```php
public function vhostData(): array;                 // data passed to the vhost template
public function supportedWebservers(): ?array;      // null = all
public function vhostTemplate(string $webserver): ?string;
public function deploymentEnvironment(): array;
public function afterDeploy(Deployment $deployment): void;
public function defaultDeploymentScript(): string;
```

#### Migration

- Move your markup into a Blade template, return its name from `vhostTemplate()`, and supply
  variables via `vhostData()`.
- `defaultDeploymentScript()` now defaults to reading `resources/deployment-scripts/{id}.sh`.
- The protected `progress()` signature changed from `progress(int $percentage)` to
  `progress(int $percentage, ?string $step)`. Overrides or callers must add the `$step` argument. It
  now also broadcasts a `site.updated` socket event and writes the new `progress_step` column.

:::warning
If you extend `AbstractSiteType` you will **not** get a fatal error, because the abstract supplies
defaults for all six new methods. But your old `vhost()` method becomes dead code that is never
called, so your site's vhost silently falls back to defaults until you migrate it.
:::

New optional tooling hooks (default to "no tooling"): `static createTimeTools(): array`,
`static requiredTooling(): array`, `static supportsTooling(): bool`.

### Web Server services

The `App\Services\Webserver\Webserver` interface changed significantly, and `AbstractWebserver`
gained two new abstract methods you must implement (or you get a fatal error):

```php
abstract public function generateVhost(Site $site, ?string $template = null): string;
abstract public function deploySplash(): void;
```

The `updateVHost()` signature changed, dropping the block-manipulation arrays. Note the default
`$restart` also flipped from `true` to `false`, and the protected helper `getUpdatedVHost()` was
removed.

```php
// 3.x
public function updateVHost(Site $site, ?string $vhost = null, array $replace = [], array $regenerate = [], array $append = [], bool $restart = true): void;
// v4.x
public function updateVHost(Site $site, ?string $vhost = null, bool $restart = false): void;
```

`changePHPVersion(Site $site, string $version): void` was **removed** from the interface and the
entire codebase. PHP-version changes are no longer driven through the web server.

New SSL-related interface methods (defaults provided by `AbstractWebserver`, so extending it is
safe): `createsSiteSSLs()`, `siteDefaults()`, `canConfigureSSL()`, `allowedSslMethods()`,
`defaultSslMethod()`. These back a reworked SSL system using a new `App\Enums\SslMethod` enum and a
new `SslType::CSR` case. `createsSiteSSLs()` reads the `creates_site_ssls` flag from your service's
data config.

### Source Control Providers

The `SourceControlProvider` interface gained four new methods; `AbstractSourceControlProvider`
supplies safe defaults (SSH port 22, no editable fields).

```php
public function getSshPort(): int;
public static function editableFields(): array;
public function editRules(array $input): array;
public function editData(array $input): array;
```

#### Migration

- Extend `AbstractSourceControlProvider` and you are safe.
- Implement `SourceControlProvider` directly and you must add all four methods, or you get a fatal
  error.

:::danger
**Security contract:** if you override `editableFields()` to return a non-empty list, you must also
override `editData()` to whitelist exactly which keys are merged. The default spread would let
callers overwrite encrypted fields like `token`.
:::

`RegisterSourceControl` is additive and non-breaking: new `->connectable(bool)` and
`->usableForSites(bool)` builders, and the registry now also emits `connectable`, `usable_for_sites`,
and an auto-derived `editable_fields`. v4.x also ships a built-in GitHub App provider.

### Isolated Users

This affects any plugin that touches a Site. v4.x introduces a dedicated `App\Models\IsolatedUser`
model, and the Site's identity attributes are now relation-backed.

- `$site->user` and `$site->ssh_key` are now accessors that fall back to `$site->isolatedUser`, and
  the underlying columns may be null. Reads still work, but writing directly to them no longer
  behaves as it did.
- `Site::changePHPVersion()` and `Site::activeSsl()` were removed.
- New helpers: `Site::fpmPoolSharedWithSiblings()`, `userSharedWithSiblings()`,
  `siblingsSharingUser()`, `htpasswdPath()`, plus tooling helpers and a `progress_step` column. Site
  now eager-loads `isolatedUser` by default.
- FPM pools and the isolated user are now shared across sibling sites of the same user, and isolation
  runs under a lock. Plugins that created or assumed one FPM pool per site should account for this.
- `config/core.php` adds a `reserved_user_names` blocklist enforced during site creation.

### New extension points and built-ins

These are additive, so no action is required.

- **Tooling system:** `App\Tooling\ToolingInterface` plus `AbstractTooling` / `AbstractMiseTooling`,
  registered via `config('tooling.providers')` (an array of class strings; there is no
  `RegisterTooling` plugin builder yet). Built-ins: Node, Bun, Yarn, Pnpm.
- **New built-ins:** SFTP storage provider, Valkey database service, `NodeSite` / `BunSite` site
  types, and an `AbstractProxiedSiteType` base class.
- **WebSocket config** moved into `config/core.php` (`ws_host`, `ws_port`, `ws_broadcast_secret`,
  `ws_allowed_origins`). The `SocketEvent::dispatch()` API itself is unchanged.