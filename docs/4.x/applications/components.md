# Components and capabilities (Beta)

A component places an [environment](concepts.md) on a server in the same project. Choose an app component for code, or a [load balancer](load-balancing.md) for routing. Placements are not movable: add a new component and remove the old one instead.

## App components

Choose a ready server, Linux user and capabilities. Required server services must already be installed. Installation prepares component directories, per-component runtime tooling where supported, and repository access. GitHub App sources use installation tokens; SSH-key sources use a component-owned deploy key.

Runtime versions managed through Mise are selected per component directory, not globally for the Linux user. Two environments can share a Linux user without forcing the same Node version. For stronger filesystem isolation, use separate users. A user's PHP-FPM pools and Site tooling may still be shared; Vito prevents removing infrastructure that another owner needs.

| Capability | What it does | Requirements |
| --- | --- | --- |
| Web | FPM, proxy or static serving according to the preset | Supported webserver; exact PHP version for FPM; process manager and start command for proxy |
| Worker | Adds a Workers tab for background processes | Process manager |
| Scheduler | Adds Cron jobs and a managed scheduled command | A non-empty command; at most one scheduler per environment |

Capabilities can be combined. Suggested commands and release-relative web directories are editable. A proxy component uses a non-privileged port; leaving it empty before deployment selects a free port from Vito's configured range. Once a release has started, the port cannot be assigned or changed, including by adding proxy web to a previously deployed component. Create a new component when a different port is needed; other web settings remain editable. Configure your process to listen on loopback and use `PORT`. A web directory cannot traverse above the release directory.

## Processes and deployment

Managed proxy and scheduler entries are changed through their capability, not the server-wide process editor. Custom workers and cron jobs are managed from their component's tabs. Server Workers and Cron jobs lists link back to the owning component as well as showing Site ownership.

Before the first release, component workers stay stopped and cron commands do nothing. After release, processes run as the component user from its current release, loading the environment through `vito-env-exec`. Do not put secrets directly into commands; use [variables](variables.md).

A ready app component added to an already deployed environment automatically joins the live release when no other run blocks it. It uses the live commit and variable snapshot, builds and starts its processes, but **does not run the release command**. A failed join is not automatically retried; review the failure and run a full deployment.

## Logs and removal

Use the deployment page for per-component stage logs. Server → Logs → Service logs includes Nginx component access/error logs, Caddy component access logs and shared PHP-FPM pool logs. Caddy errors remain in its global error log. A catalogue entry can exist before its file is created by the first request.

Remove domains targeting a component before deleting it or removing its web capability. Active deployments block conflicting component mutations. Deletion removes owned processes, deploy keys, routing and resource access while preserving other Sites/components and any shared Linux user they still need. Failed cleanup retains state for retry; do not manually delete its ownership records.
