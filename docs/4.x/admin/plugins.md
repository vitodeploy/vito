# Plugins

## Introduction

The Plugins page in the admin area is where you install and manage plugins for your Vito instance.
Plugins extend Vito with new providers, services, site types, features, and more.

:::info
This page covers managing plugins from the admin area. To build your own plugin, see the
[Plugin development guide](/docs/4.x/plugins).
:::

## Browsing and installing

From **Admin > Plugins**, the **Official** and **Community** tabs let you browse available plugins.
Official plugins are developed and maintained by the Vito team; community plugins are contributed by
the community. Click install on a plugin to add it to your instance.

## Enabling and disabling

Installed plugins appear in the **Installed** tab. A plugin must be **enabled** before Vito boots it.
Use the menu on each plugin to:

- **Enable** - start booting the plugin so its features become available.
- **Disable** - stop booting the plugin. Its code stays in your instance and can be re-enabled later.
- **Uninstall** - remove the plugin's code from your instance entirely.

## Discovering local plugins

If you are developing a plugin locally, the **Discover** tab lists plugins found in your instance's
plugin directory so you can install them. See the
[Plugin development guide](/docs/4.x/plugins#local-setup) for details.

## Updating plugins

Click **Check for updates** to see whether any installed plugins have a newer release. When an update
is available, you can update the plugin from its menu.

## Errors

If a plugin throws an error while booting, Vito disables it automatically and shows the error in this
page. You can view the error logs for a plugin to debug it.
