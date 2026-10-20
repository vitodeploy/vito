# Resources (Beta)

Attach a new or existing database, or an installed Redis/Valkey service, from an environment's **Resources** page. Vito manages connection variables and component access; it does not provision a new database server. Existing server backups remain separate.

## Before attaching

The resource and app servers must belong to the application's project. Same-server access uses loopback. Remote components require a shared available private network, and the resource server needs a ready firewall with IPv4 and IPv6 enforcement.

::: warning Strict remote access
Attaching restricts the resource's service port to attached app component sources. This is **service-port-wide**, not isolation of one database row: unrelated remote Sites, tools and clients may lose access. Existing ordinary database users/data are preserved, but their remote reachability is not guaranteed. Networking changes can restart the service. Redis/Valkey needs password authentication, including for local clients.
:::

Read the confirmation warning and plan downtime/access changes before attaching. Wait until the attachment is **Ready** before deploying. Failures preserve cleanup information and offer Retry. Attach/detach operations require an idle environment.

## Credentials and variables

Database attachments generate separate managed credentials rather than asking you to reuse an ordinary user's password. Redis/Valkey uses the service's encrypted password. Credentials are encrypted in Vito and masked in the dashboard.

One attachment can be the default of each kind. Additional attachments use uppercase aliases, for example `ANALYTICS`. The alias prefixes all generic variables with `ANALYTICS_`.

| Resource | Generic variables |
| --- | --- |
| Database | `DATABASE_URL`, `DATABASE_HOST`, `DATABASE_PORT`, `DATABASE_NAME`, `DATABASE_USER`, `DATABASE_PASSWORD` |
| Redis/Valkey | `REDIS_URL`, `REDIS_HOST`, `REDIS_PORT`, `REDIS_PASSWORD` |

URLs encode credentials and bracket IPv6 hosts. Values resolve per component: a local connection may use a different host from a remote one. Presets may add aliases; Laravel supplies `DB_*` and a `REDIS_PREFIX` based on the application/environment. The core does not assume those framework names. Resource defaults are read-only in the Variables page, but an explicit environment variable with the same key overrides them. Save and deploy to apply changes.

The suggested Laravel Redis prefix uses name slugs, not immutable IDs. Similar names can share a prefix, and renaming an application changes it on the next deployment. When sharing Redis, set an explicit, unique `REDIS_PREFIX` environment variable (or `{ALIAS}_REDIS_PREFIX`) to keep the namespace stable. A prefix is not an access-control boundary.

## Access lifecycle

Adding/removing components or changing private addresses reconciles managed users and firewall rules. Vito blocks changes that would remove required services, firewall protection, databases or managed credentials while attached. Managed rules/users are not edited through ordinary server controls.

PostgreSQL attachments use a durable managed owner for newly created objects, allowing replacement components to continue DDL work. Attaching an existing database does not transfer ownership of every preexisting object. Review its existing ownership and permissions separately.

## Detach

Detaching removes attachment-owned users/grants and firewall access, **not the database, stored data, ordinary users or the service's networking setting**. Redis/Valkey keeps its configured password. Apps using the managed credentials can lose access immediately; redeploy to remove stale variables. Interrupted creation can leave a failed database catalogue entry for explicit cleanup.

Environment/application/server deletion performs resource-access cleanup before deleting ownership records. Retry failed cleanup rather than bypassing these guards. Resource attachment IDs protect deployment/join resource-set consistency; they are not frozen snapshots of every credential, address or network generation.
