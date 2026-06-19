# Site Isolation

Every site in VitoDeploy runs under a dedicated, non-privileged system user. Running each site as its own user
keeps sites separated from one another at the operating-system level, so a compromise of one site cannot reach another.

## How it works

When you create a site you choose an **isolated user**. VitoDeploy provisions that user on the server, and the site's
files live under the user's home directory (e.g. `/home/your-user/domain.com`). The site's PHP-FPM pool, processes,
and deployments all run as that user.

## Sharing a user across sites

In v4.x an isolated user can be **shared by multiple sites on the same server**, rather than being tied to a single
site. This is useful when several sites belong to the same project or owner and you want them to share files, runtime
versions, and tooling.

When creating a site, the **User** field lets you either:

- **Select an existing isolated user** on the server. Each user shows how many sites already use it.
- **Create a new user** by typing a username that does not yet exist.

Sites that share a user also share that user's PHP-FPM pool and its installed
[tooling](/docs/4.x/sites/site-tooling) and runtime versions.

:::info
If you prefer the older behaviour, simply give every site its own unique user. Sharing is optional.
:::

## Usernames

A username must:

- Be between 3 and 32 characters long.
- Start with a lowercase letter or underscore, and end with a lowercase letter or digit.
- Contain only lowercase letters, digits, hyphens, and underscores.

### Reserved names

To prevent sites running as system or service accounts, VitoDeploy blocks a list of **reserved usernames**. Names such
as `root`, `www-data`, `nginx`, `mysql`, `ubuntu`, and `vito` cannot be used for an isolated user. The full list is
defined in `config/core.php` under `reserved_user_names`.

## Deleting sites and shared users

When you delete a site, VitoDeploy only removes the isolated user and its PHP-FPM pool if **no other site on the server
is still using them**. If the user is shared with sibling sites, the user and pool are left in place and only the
deleted site's own resources are removed.

:::info
Operations that create, modify, or delete an isolated user run under a lock, so concurrent site actions cannot leave a
user or its FPM pool in an inconsistent state.
:::

## Why Site Isolation

Site Isolation is a security best practice. By running each site (or group of related sites) under its own system user,
if one site is compromised and an attacker gains access to that user, they still cannot reach sites that run under a
different user.
