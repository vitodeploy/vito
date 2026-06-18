# Laravel Octane Plugin

## Introduction

VitoDeploy provides a first party plugin to setup Laravel Octane on your server.

## Supported Web Servers

- Nginx

## Supported site types

- Laravel

## Supported Methods

You can setup Laravel Octane on your server in one of the following ways:

## How it works

Vito will use your Laravel project to run a worker command to start the Laravel Octane server. Then it will modify your site's virtual host configuration to reverse proxy requests to your Laravel Octane instance.

## Installation

To install the plugin, navigate to the `Admin -> Plugins` and select `Laravel Octane` plugins in the `Official` tab.

## Enable as a site feature

After you successfully setup a Laravel site, You can navigate to the `Features` side menu item, and Enable the `Laravel Octane` feature.

When enabling, you will need to provide the following information:

**Port**: The port that your Laravel Octane instance will run on. Make sure this port is not used by any other service on your server.

:::info
The port won't be exposed to internet, it will be used internally by the web server to proxy requests to your Laravel Octane instance.
:::

:::warning
Make sure you have already set the `OCTANE_SERVER` in your `.env` file
:::

:::info
Make sure you read [Laravel's official documentation](https://laravel.com/docs/12.x/octane).
:::

## Uninstall

To uninstall the plugin, navigate to the `Admin -> Plugins` and you can find the `Laravel Octane` plugins in the `Installed` tab and you can uninstall it.
