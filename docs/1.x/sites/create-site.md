# Create Site

Vito makes website deployments painless and easy.

To create a site you need to fill the mandatory fields.

## Site Type

Vito is built to deploy PHP applications. Currently it supports any PHP applications.

But it has a separate site type for Laravel to support more features of it in the future.

Site Types are:

- PHP (Any other PHP applications)
- Blank PHP
- Laravel
- WordPress
- PHPMyAdmin

## Domain

The domain that you want to deploy your website to

## Alias

This is an optional field which you can load the website under another domain/subdomain but mainly it is there to also support `www` subdomain of your website.

## PHP Version

You can select any PHP version that you've installed in the server.

## Web Directory

This is the `public` directory of your website, Better to say that it is the `root` directory that the webserver (Nginx) points to.

## Source Control

Here you specify which source control connection you want to use. To connect to the source controls you can click on the `Connect` button.

Read about them more [Here](../settings/source-controls)

## Repository

Enter the repository of your project. For example if it is on Github it will be something like this:
`organization/repository`

## Branch

The default branch is `main` but if it is different, Then you need to specify here.

## Composer Install

If you want to run `composer install` after cloning the repository click the checkbox.

If you check this, It will run the following command on the installation process after cloning the repository.

```sh
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev
```
