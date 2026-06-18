# Settings

## Introduction

In the Settings page you can manage your site's details, its PHP version, web directory, vhost
template, basic auth, and more.

## Site details

The Settings page shows read-only details for the site, including its ID, domain, type, repository,
path, status, and creation date. Several of these have inline controls to change them, described
below.

## Change PHP Version

You can change the PHP version of each website in their Settings page.

Make sure that the PHP version you want to use is already installed in the [PHP](../servers/php#install-and-uninstall)
page.

## Change branch

You can change the branch of your cloned repository in the Settings page.

## Change source control

You can change the source control of your cloned repository in the Settings page.

## Web directory

You can change the web (public) directory of your site relative to its root path, for example
`/public` for a Laravel application. Updating it regenerates the vhost.

## Aliases

Site aliases are no longer managed here. In v4.x they are handled as alias
[domains](/docs/4.x/sites/domains), where you can add multiple alias and redirect domains per site,
each with its own DNS validation and SSL configuration.

## VHost

In v4.x the vhost is generated from a [Mustache](https://mustache.github.io) template, replacing the
custom-block model used in earlier versions. The Settings page gives you two related controls.

### View VHost

**View VHost** opens a read-only view of the vhost that is currently deployed to the server, so you
can see exactly what Vito generated.

### VHost Template

**Edit Template** opens the Mustache template used to generate the vhost. From here you can:

- **Edit** the template with syntax highlighting for your webserver (nginx or Caddy).
- **Preview** the generated output from your edits before saving, without touching the live config.
- **Reset** the template back to the default, which regenerates the vhost and discards your
  customizations.

Changes to the template persist across SSL, domain, and redirect updates. When a site uses a
customized template, the Settings page flags it so you know it differs from the default.

:::tip
See the [Mustache manual](https://mustache.github.io/mustache.5.html) for the template syntax.
:::

### VHost generation

Automatic vhost generation can be enabled or disabled per site. While it is disabled, changes to
SSL, domains, or redirects will not update the vhost on the server.

:::warning
On sites that existed before upgrading to v4.x, vhost generation is **disabled by default** so the
upgrade does not overwrite a working configuration. Review the template, then re-enable generation
(a banner on the site provides a quick **Re-enable** action). See
[Custom VHost Configuration](/docs/4.x/prologue/breaking-changes) for details.
:::

## Basic Auth

For sites served by nginx or Caddy, you can protect the site with HTTP Basic Authentication, adding
one or more username/password pairs. This is useful for staging and preview sites that must be
publicly reachable but not openly accessible. Basic auth is managed from the Settings page and shows
whether it is currently enabled and how many users are configured.

## Delete

You can delete the website from your server.

This will delete the files of your website and the webserver configurations related to your website
from the server.
