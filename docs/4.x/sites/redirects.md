# Site Redirects

## Introduction

Vito supports URL redirections for your sites. A redirect maps a path on your site to another location — either by
returning an HTTP redirect response (301/302/307/308) or by transparently reverse-proxying the request to another
service (Proxy mode).

This is useful when you want to move a path to a new URL, point part of your site at another application, or expose a
background service (such as a WebSocket server) through your site's domain.

:::info
This page covers path-level redirects. To redirect a whole domain to your site's primary domain, add
a redirect domain on the [Domains](./domains.md) page instead.
:::

:::warning
Creating, editing, or deleting site redirects will regenerate the Nginx vhost file and any manual changes to the Nginx
vhost will be lost.
:::

## How redirects are applied

Each redirect is stored against your site and rendered into the site's web server configuration. When you create, edit,
or delete a redirect, Vito connects to the server over SSH, regenerates the vhost file, and reloads the web server. The
redirect moves through a short status lifecycle (`creating` → `ready`) while this happens, and the row updates in place
once it is applied.

Both **Nginx** and **Caddy** are supported — Vito generates the appropriate directives for whichever web server your
site uses.

## Supported Redirect Types

Vito supports the following redirect modes.

### 301 Moved Permanently

Indicates that the requested resource has been **permanently** moved to the URL in the `Location` header. Browsers and
search engines cache this aggressively — use it only when the move is permanent.

### 302 Found

Indicates that the requested resource has been **temporarily** moved to the URL in the `Location` header. The original
URL should keep being used in the future.

### 307 Temporary Redirect

Like a 302, but guarantees that the request **method and body are not changed** when the redirected request is made.
Prefer this over 302 for non-`GET` requests.

### 308 Permanent Redirect

Like a 301, but guarantees that the request **method and body are not changed**. Prefer this over 301 for non-`GET`
requests.

### Proxy

Instead of returning an HTTP redirect, the Proxy mode reverse-proxies requests from the given path to the target URL
(e.g. proxy `/docs` to `https://docs.example.com`). The path stays the same in the browser while the content is served
from the target.

:::info
In Proxy mode the **From** field is used as an Nginx `location` expression, so you can use a prefix (`/docs`) or a
regular expression (`~ ^/app(s)?/`). The other modes use an exact match.
:::

#### WebSocket support

When using the Proxy mode, you can enable **WebSocket support** to allow the proxied connection to upgrade to a
WebSocket. This adds the required upgrade headers to the generated configuration so that persistent WebSocket
connections work through the proxy.

Enable this when the target serves WebSocket traffic — for example when proxying to a Laravel Reverb server (see the
[example](#example-laravel-reverb-over-websockets) below).

:::info
WebSocket support only applies to the `Proxy` mode. Caddy proxies WebSocket connections automatically, so this option
only affects Nginx.
:::

## Create a Redirect

To create a redirect, fill in the following fields:

- **Mode**: The redirect mode to use (e.g. `301 Moved Permanently` or `Proxy`).
- **From**: The path to redirect from. For redirect modes this is an exact path (e.g. `/old-path`); for Proxy mode it is
  an Nginx location expression (e.g. `/docs` or `~ ^/app(s)?/`). It must not start with `http://` or `https://`, and
  must be unique for the site.
- **To**: The destination. For redirect modes, a full URL (e.g. `https://example.com/new-path`). For Proxy mode, the
  address of the target service, which may be an internal address (e.g. `http://0.0.0.0:8089`).
- **WebSocket support**: Only shown when the mode is `Proxy`. Enable it to proxy WebSocket connections (see
  [WebSocket support](#websocket-support)).

## Edit a Redirect

You can edit an existing redirect from its row actions in the redirects table. Editing lets you change the **From**,
**To**, **Mode**, and **WebSocket support** of a redirect without having to delete and recreate it. Saving your changes
regenerates the vhost file and reloads the web server.

## Delete a Redirect

Deleting a redirect from its row actions removes it from the site's configuration and regenerates the vhost file. The
proxied or redirected path stops responding once the change is applied.

## Example: proxy a path to another service

To serve documentation hosted elsewhere under `/docs` on your own domain:

- **Mode**: `Proxy`
- **From**: `/docs`
- **To**: `https://docs.example.com`

Requests to `https://your-site.com/docs` are now served transparently from `docs.example.com` without changing the URL
in the browser.

## Example: Laravel Reverb over WebSockets

You can expose a [Laravel Reverb](https://laravel.com/docs/reverb) server running on your site through a proxy redirect
with WebSocket support, instead of using the deprecated Laravel Reverb plugin.

Assuming Reverb is running on your server on internal port `8089` (started by a [worker](../servers/workers.md)), create
the following redirect:

- **Mode**: `Proxy`
- **From**: `~ ^/app(s)?/`
- **To**: `http://0.0.0.0:8089`
- **WebSocket support**: `Yes`

Requests to `/app` and `/apps` on your site are now proxied to Reverb with the WebSocket upgrade headers in place. For
the full walkthrough — including the `.env` variables, the deployment script change, and the worker — see
[Setting up Laravel Reverb manually](../plugins/laravel-reverb.md#setting-up-laravel-reverb-manually).
