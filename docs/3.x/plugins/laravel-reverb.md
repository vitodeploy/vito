# Laravel Reverb Plugin

## Introduction

VitoDeploy provides a first party plugin to setup Laravel Reverb on your server.

## Supported Web Servers

- Nginx
- Caddy

## Supported site types

- Laravel

## Supported Methods

You can setup Laravel Reverb on your server in one of the following ways:

## How it works

Vito will use your Laravel project to run a worker command to start the Laravel Reverb server. Then it will modify your site's virtual host configuration to reverse proxy requests to your Laravel Reverb instance.

If you use the site feature method, Vito will modify the website's vhost to reverse proxy `/app` and `/apps` to your Laravel Reverb instance.

If your app already using those paths, You will need to use the Site Type method instead.

## Installation

To install the plugin, navigate to the `Admin -> Plugins` and select `Laravel Reverb` plugins in the `Official` tab.

## Enable as a site feature

After you successfuly setup a Laravel site, You can navigate to the `Features` side menu item, and Enable the `Laravel Reverb` feature.

When enabling, you will need to provide the following information:

**Port**: The port that your Laravel Reverb instance will run on. Make sure this port is not used by any other service on your server.

:::info
The port won't be exposed to internet, it will be used internally by the web server to proxy requests to your Laravel Reverb instance.
:::

**Command**: The command to start your Laravel Reverb instance. The default command is `php artisan reverb:start --host=0.0.0.0 --port=REVERB_PORT`. You can change it if you have a custom command to start your Laravel Reverb instance.

:::warning
The port on the command should match the port you provided above.

The host must be `0.0.0.0`
:::

:::info
Make sure you read [Laravel's official documentation](https://laravel.com/docs/12.x/reverb) for the correct command.
:::

## Enable as a site type

After installing the plugin, There will be a new site type appearing when you're selecting a site type to create a new site.

You can select the `Laravel Reverb` site type, and provide the same information as the [site feature method](#enable-as-a-site-feature).

You'll need to change the `REVERB_HOST` in both sites (Laravel and Reverb) to point to the domain of the Reverb website.

## Example Project

This simple Laravel project demonstrates how to use Laravel Reverb with VitoDeploy.

[Project on Github](https://github.com/saeedvaziry/laravel-reverb-demo)

## Uninstall

To uninstall the plugin, navigate to the `Admin -> Plugins` and you can find the `Laravel Reverb` plugins in the `Installed` tab and you can uninstall it.
