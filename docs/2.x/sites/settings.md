# Settings

## Introduction

In the Settings page, you can manage your website settings like its PHP version, Aliases, Custom Vhost and more.

## Change PHP Version

You can change the PHP version of each website in their Settings page.

Make sure that the PHP version you want to use is already installed in the [PHP](../servers/php#install-and-uninstall)
page.

## Update Aliases

You can add/remove site aliases. It will update the aliases on your site's nginx vhost configuration.

:::warning
Note that if you've made any changes to the VHost manually, It will override it.
:::

## Update VHost

It is possible to customize the virtual host configuration of your webserver (Nginx). However, it is not recommended to
do so unless you know what you are doing.

Vito will show you the current configuration of the site, and you can modify it as you wish.

:::danger
Nginx vhost file will get reset if you generate or modify SSLs, Aliases, or create/delete site redirects.
:::

## Delete

You can delete the website from your server.

This will delete the files of your website and the webserver configurations related to your website from the Server.
