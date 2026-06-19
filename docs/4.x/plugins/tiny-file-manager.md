# Tiny File Manager Plugin

:::warning
File managers are not recommended to use on Production servers. Use at your own Risk.
:::

## Introduction

[Tiny File Manager Plugin](https://github.com/vitodeploy/tiny-file-manager-plugin) provides an integrated way to install [Tiny File Manager](https://tinyfilemanager.github.io/) on your server.

## Supported Web Servers

- Nginx
- Caddy

## Supported Methods

You can setup Tiny File Manager on your server in one of the following ways:

- [Site type (separate subdomain)](#enable-as-a-site-type)

## How it works

Tiny File Manager is a single PHP file that you can place in your website's root directory. VitoDeploy will help you to create a new site with Tiny File Manager as the site type, and it will automatically configure the web server to serve the Tiny File Manager.

## Installation

To install the plugin, navigate to the `Admin -> Plugins` and select `Tiny File Manager` plugins in the `Official` tab.

## Enable as a site type

After installing the plugin, There will be a new site type appearing when you're selecting a site type to create a new site.

You can select the `Tiny File Manager` site type, and provide the required information including admin password and path to manage.

## Uninstall

To uninstall the plugin, navigate to the `Admin -> Plugins` and you can find the `Tiny File Manager` plugins in the `Installed` tab and you can uninstall it.
