# Release Notes

## Laravel 13 and Inertia v3

Vito v4.x has been updated to the latest versions: Laravel 13, Inertia.js 3, and Vite 8. This brought
refactors to plugin caching and CSRF middleware, and keeps Vito current with the latest framework
releases.

### Inertia context shrinking

Inertia responses previously shipped ~120 KB+ of mostly-static shared data on every request (the
Ziggy route table, the provider/service catalogue, and more). A new cached bootstrap endpoint now
serves this once and hydrates it on the frontend, cutting typical responses down to a few KB.

## Realtime websocket Updates

Vito now ships a built-in WebSocket server that pushes live updates across all async operations -
deployments, servers, services, sites, SSLs, backups and more - replacing the old polling-based
refresh. The header shows connection status with manual reconnect, and connections survive page
navigation.

## GitHub App registration

Connect source control via a GitHub App, a more secure alternative to personal access tokens and
deployment keys. Register an app against your instance (automatically or manually), and deployments
authenticate with short-lived, per-repo tokens that never touch disk.

## Site improvements

### Resume failed site deployments

Sites that fail during installation now enter a failed state instead of being lost. A failure banner
surfaces a friendly error summary, and a Retry action resumes the install idempotently from where it
left off.

### Shared isolated users

Isolated users can now be shared across multiple sites on a server rather than being tied to one.
This release adds default username suggestions, distributed locking to prevent race conditions, and
a reserved-name list that blocks system accounts from being used as site users.

### Hosted domains

Manage multiple alias and redirect domains per site. Domains that don't yet resolve are held in a
pending state and re-checked automatically (or validated on demand), each domain can have its own SSL
option, and vhost generation is driven by editable nginx/Caddy templates with preview.

### Site tooling

Per-site tooling management has been expanded and standardised, with install and uninstall actions.
Tooling now correctly runs as the site's isolated user and is available across all site types, not
just PHP. The available tools are Node.js, Bun, Yarn, pnpm, and Composer, each installed per isolated
user.

### Site stats (beta)

Install the new GoAccess service to get per-site traffic statistics. Stats appear per site, refresh
automatically every hour, and can be enabled or disabled individually.

### Bun & Node site types

Vito now supports Bun and Node.js sites natively, with per-site version management via Site Tooling.
PHP/Laravel sites can optionally install Node/Bun alongside them where needed. 

### Site Alerts

Vito now tracks and displays alerts per site, both on the table listings and with more information on each
site page.  Multiple alerts collapse automatically, and most provide a call to action.  Alerts can include
when domains no longer resolve to the server, VHost generation is turned off, or a Worker has failed to start.

### HTTP basic auth

Protect sites with HTTP Basic Authentication on both nginx, Caddy and Apache. This is useful for staging and
preview sites that must be publicly reachable but not openly accessible. Users and passwords are
managed from the site settings UI.

## Server improvements

### Server networking (IP management)

Vito can now manage a server's IP addresses. Add individual addresses or whole ranges, set the
interface and prefix length, and mark a primary public and private IP for the server. Addresses you
add are listed alongside the ones Vito detects, and you can refresh at any time to pick up changes
made on the server.

### Server security

A new Security section gives each server a hardening dashboard. From here you can install and
configure fail2ban, manage the UFW firewall, toggle SSH password authentication and root login, and
enable automatic package updates on a schedule. Vito shows an overall security score and can run an
on-demand check to confirm the server's live state matches your settings.

### Live terminal

The server console is now a full, interactive terminal in the browser. Vito opens a real SSH shell to
the server and streams it over a WebSocket, so it is stateful (commands like `cd` carry over) and
supports interactive programs such as `top`, `vim`, and `nano`. You can choose which user to connect
as and open more than one session at a time.

### Valkey

[Valkey](https://valkey.io) joins Redis as a supported in-memory data store. Install it as a service
from the Services page (a server can run one in-memory service at a time), with its logs available in
the Service Logs viewer.

### Service Log viewer

A new Service Logs section in the server logs menu exposes logs for installed services automatically.
You can change the number of lines shown, download the full file, or clear a log.

### Monitoring updates

Server monitoring has been extended to collect and visualize a much wider range of system metrics,
with supporting database, API, and frontend updates.

### Server Alerts

Vito now tracks and displays alerts per server, both on the table listings and with more information on each
server page.  Multiple alerts collapse automatically, and most provide a call to action.  Alerts can include
when a server needs a restart, or when updates to packages are available.

### Apache support (beta)

Apache joins nginx and Caddy as a supported webserver. The config generation system was refactored
for extensibility, removing hard-coded webserver assumptions throughout the codebase, Apache joins Vito 
in beta, and we would not recommend using on a production server just yet. 

### SSL updates, including wildcard certs

SSL certificates can now be managed directly at the server level, in addition to per-site. This adds
custom certificates, CSR generation and download, and auto-renewing wildcard Let's Encrypt certificates 
for domains managed by Vito.

## Other improvements

### API key project scoping

API keys can now be scoped to specific projects and granted read-only or read-and-write access,
instead of always having full account access. Leave the project list empty for a key that works
across all of your projects.

### SFTP storage

A new SFTP storage provider lets you store backups on any SFTP server. Provide the host, port, path,
and credentials, and Vito verifies the connection before saving.
