# PHP

Vito enables you to manage your installed PHP versions and even switch between them.

## Install new PHP

You can install other PHP versions alongside your current PHP versions and you can have all of them in your server of course.

## Default PHP Cli

Although you can have only one default PHP Cli which can be called by `php` command on the server. Here you can switch the default cli version.

## Edit php.ini

Under the `Actions` dropdown of each PHP version you can Edit it's `php.ini`. This will fetch the ini file from the server and enables you to modify and after you are done with it, It will upload the new `php.ini` into the server and restarts the `php-fpm`.

## Restart

You have this option always to restart each PHP FPM service in the PHP page or in the [Services](./services) page.

## Uninstall

Vito gives you this option of uninstalling a specific PHP version.

:::danger
If you have a site running with the PHP that you want to uninstall, You need to first change the PHP version of the website first.
:::

## Install PHP Extensions

Vito supports some of the PHP extensions and enables you to install them.

To install a new PHP extension you can go to the `PHP` page and for each PHP version you can click on the `Actions` dropdown and Install a new extension.

Available extensions to install:

- imagick
- exif
- gmagick
- gmp
- intl
- opcache
