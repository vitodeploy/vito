# Domains and SSL (Beta)

Environment domains route hosts to an explicit ready web component or [load balancer](load-balancing.md) in that environment. A load balancer's public domains and certificates live on its server; its internal backends do not need public domains.

## Add and verify a domain

1. Open the environment's **Domains** page and add a host.
2. Choose its target component and SSL method.
3. Point DNS at the target server, then use **Verify now**, or wait for the pending-domain check.
4. Wait for verification and activation before expecting the application route to serve traffic.

A new or materially changed domain stays pending until DNS and the HTTP verification challenge identify the target server. Pending domains do not receive an application vhost or certificate. Host collisions, including wildcard overlaps, are checked against Sites and other environment domains on that server. The same host on a different server is not inherently a collision.

## Aliases and redirects

An alias serves the target component. A redirect points to a non-wildcard alias in the same environment and inherits its target component. Redirects cannot chain to another redirect. Remove dependent redirects before deleting, converting or retargeting an alias.

Wildcard hosts need a matching project Domain with a project-owned DNS provider for DNS-01 validation. This is separate from adding an ordinary environment host.

## Certificates and HTTPS

- **None** serves without an explicitly configured certificate.
- **Let's Encrypt** uses the target webserver's supported issuance path. Nginx certificates are owned by the environment; wildcard issuance uses DNS-01.
- **Custom** selects an active, unexpired server-level certificate installed on the target server and covering the host.

Force SSL is per domain and requires usable TLS. Caddy manages non-wildcard automatic HTTPS itself: Vito route activation is not a separate certificate-readiness probe. Check Caddy logs when issuance is still pending or fails. Explicit certificates and renewals retain their normal validity checks.

Generated vhosts use the preset's FPM socket, proxy address or static directory. Invalid candidate configurations are rejected before reload. Editing raw application vhosts is not supported.

## Removal

Remove a component's domains before deleting it or removing its web capability. Environment/application deletion removes domains before components and cleans up unused environment-owned certificates. A server-level custom certificate is not adopted as an environment-owned certificate. Failed cleanup can be retried without discarding ownership records.
