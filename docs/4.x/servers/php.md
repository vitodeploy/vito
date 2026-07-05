# PHP

## Introduction

Vito supports multiple PHP versions, and you can install and uninstall them during the server creation or after the
server creation in the `PHP` menu in the server page or in the [Services](./services.md) page.

## Supported PHP Versions

- PHP 5.6
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
- PHP 8.5

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
- sqlite3
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

## Troubleshooting

### `apt-get update` fails with "Repository ... changed its 'Label' value"

When installing a PHP version, or updating a server that already has PHP installed, you may see an
error like this:

```text
E: Repository 'https://ppa.launchpadcontent.net/ondrej/php/ubuntu noble InRelease' changed its 'Label' value from 'PPA for PHP' to 'Use packages.sury.org/php instead'
```

Vito installs PHP from the `ondrej/php` Launchpad PPA. That PPA is being merged into
[packages.sury.org](https://packages.sury.org/php/), and as part of the move it changed the `Label`
field in its release metadata to announce this. APT treats a changed `Label` (along with `Origin`,
`Suite`, or `Version`) as a potential security concern and refuses to refresh that repository's
package list unless you explicitly allow it — which aborts the whole `apt-get update`, and with it
the PHP install or server update that triggered it.

Vito keeps installing PHP from the `ondrej/php` PPA (the `packages.sury.org` mirror doesn't yet
carry every extension package Vito needs, such as `php-redis`, for all PHP versions), and now passes
`-o Acquire::AllowReleaseInfoChange::Label=true` to `apt-get update` so this specific, expected label
change no longer blocks installs or updates.

If a server is still stuck on the old error, run this once on the server as the `vito` user instead
of waiting for a PHP install/reinstall or server update to pick up the fix:

```sh
sudo apt-get update -o Acquire::AllowReleaseInfoChange::Label=true
```

:::info
After that, retry the PHP install or server update in Vito — it should complete normally.
:::