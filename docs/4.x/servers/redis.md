# Redis

Redis is an optional service. You can install it during server creation or later from the
[Services](./services) page. Vito installs the latest version of Redis and binds it to `0.0.0.0` so
you can access it from outside if Redis's port is open on the [Firewall](./firewall).

:::warning
Important to know that Redis has its default port `6379` and it is closed on the Firewall by default
:::

## Valkey

[Valkey](https://valkey.io/) is an open-source, high-performance, Redis-compatible fork. Vito ships it
as a separate service that you can install from the [Services](./services) page as a drop-in
alternative to Redis. A server can run Redis or Valkey, and they behave the same from the application's
point of view.