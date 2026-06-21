# PHP

## Introduction

Vito supports multiple PHP versions, and you can install and uninstall them during the server creation or after the
server creation in the `PHP` menu in the server page or in the [Services](./services) page.

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

## Default PHP Cli

Although you can have only one default PHP Cli which can be called by `php` command on the server, But you can switch
the default cli version in the `PHP` page.

## Edit php.ini

Vito enables you to edit the `php.ini` file of each PHP version. You can edit both the `php.ini` file of the CLI and the
FPM.

## Restart

You have this option always to restart each PHP FPM service in the PHP page or in the `PHP` menu
or [Services](./services) page.

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
