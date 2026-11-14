# Load balancing (Beta)

A load balancer component routes an environment's public traffic to app/web components or external IP/port backends. It requires a ready Nginx or Caddy webserver, but no runtime, deploy key, application directory or capabilities.

## Configure routing

1. Add a **Load balancer** component.
2. Open its component page and edit the method and backends.
3. Choose ready web components in the same environment, or enter external IP addresses and ports.
4. Add public [domains](domains.md) targeting the load balancer.

Internal component backends use loopback on the same server or a shared available private network across servers. A server's legacy local-IP field alone is not a private-network membership. Missing connectivity is rejected. Vito prepares private backend listeners, trusted forwarding and firewall rules allowing the corresponding load balancer sources.

External IP/port backends receive **no Vito-managed listener or firewall**. You are responsible for reachability, access restrictions, the service and its handling of forwarded headers. IPv4 and IPv6 addresses are supported.

## Methods and health

The available methods follow the selected webserver. Nginx supports weights and backup backends, except backups with IP hash. Caddy accepts only default weight and no backup flag; unsupported settings are rejected rather than silently ignored. Duplicate backends and all-backup pools are rejected.

Health handling is passive, based on failed requests. There is no active health-check service. Empty or unavailable pools stop forwarding rather than falling back to an unrelated target. The overview topology shows configuration and lifecycle status, not live reachability.

## Deployment and cleanup

Load balancers reload configuration during the release stage of full deployments and environment rollbacks. They skip clone, scripts and automatic live-release joins. Reload failure participates in the deployment's release failure/recovery handling.

Removing a backend component/web capability or deleting a server reconciles remaining routes and firewall rules. Removing relied-on network membership is blocked; remove the backend relationship first. Provider-observed network disappearance instead withdraws unavailable routes and reports an error for reconciliation. Keep networking and webserver jobs running, and inspect failed jobs before retrying cleanup.
