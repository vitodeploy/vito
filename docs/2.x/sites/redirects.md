# Site Redirects

## Introduction

Vito supports URL redirections for your sites. You can redirect your site to another URL or path. This is useful when
you want to redirect your site to a new domain or a new path.

:::warning
Creating or deleting site redirects will regenerate the Nginx vhost file and any manual changes to the Nginx vhost will be lost.
:::

## Supported Redirect Types

Vito supports the following types of redirects:

### 301 Moved Permanently

The HTTP 301 Moved Permanently redirection response status code indicates that the requested resource has been
permanently moved to the URL in the Location header.

### 302 Found

The HTTP 302 Found redirection response status code indicates that the requested resource has been temporarily moved to
the URL in the Location header.

### 307 Temporary Redirect

The HTTP 307 Temporary Redirect redirection response status code indicates that the resource requested has been
temporarily moved to the URL in the Location header.

### 308 Permanent Redirect

The HTTP 308 Permanent Redirect redirection response status code indicates that the requested resource has been
permanently moved to the URL given by the Location header.


## Create a Redirect

To create a redirect, you need to fill the following fields:

- **From**: The path you want to redirect from. (e.g. `/old-path`)
- **To**: Full URL or path you want to redirect to. (e.g. `https://example.com/new-path`)
- **Mode**: The type of redirect you want to create. (e.g. 301 Moved Permanently)
