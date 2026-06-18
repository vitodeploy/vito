# PHP

## Introduction

Vito supports multiple PHP versions, and you can install and uninstall them during the server creation or after the
server creation in the `PHP` menu in the server page or in the [Services](./services.md) page.

## Supported PHP Versions

- PHP 7.0
- PHP 7.1
- PHP 7.2
- PHP 7.3
- PHP 7.4
- PHP 8.0
- PHP 8.1
- PHP 8.2
- PHP 8.3
- PHP 8.4

## Install and Uninstall

Vito gives you the option to easily install and uninstall different PHP versions.

:::warning
You cannot uninstall a PHP version if you have a site running with that PHP version. You need to
first [change](../sites/settings#change-php-version) the PHP version of the website first.
:::

## Install PHP Extensions

Vito supports some of the PHP extensions and enables you to install them.

To install a new PHP extension you can go to the `PHP` page and for each PHP version you can click on the `Actions`
dropdown and Install a new extension.

Available extensions to install:

- imagick
- exif
- gmagick
- gmp
- intl
- opcache

:::info
You can also [develop a plugin](../plugins.md#register-services) to add a new PHP extension to Vito.
:::

## Default PHP Cli

Although you can have only one default PHP Cli which can be called by `php` command on the server, But you can switch
the default cli version in the `PHP` page.

## Edit php.ini

Vito enables you to edit the `php.ini` file of each PHP version. You can edit both the `php.ini` file of the CLI and the
FPM.

## FPM service

Under the `PHP` or `Services` page, you can `start`, `stop`, `restart`, `enable`, or `disable` the FPM service for each
PHP version.