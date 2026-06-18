# Site Types

## Introduction

Vito is built to support deploying PHP applications and currently, it supports any PHP applications. But it has site
types for provide more features out of the box for some specific PHP applications.

## Supported Site Types

- PHP (Any other PHP applications)
- Blank PHP
- Laravel
- Node.js with NPM
- WordPress
- PHPMyAdmin
- Load Balancer

### PHP

You can deploy any PHP application with this site type if you have the source code on
any [supported source control providers](../settings/source-controls#supported-providers).

**Note:**

To deploy a site this type, You need to have a Webserver and PHP installed on your server in
the [Services](../servers/services) section.

### Blank PHP

Blank PHP site type is simply a blank php website that doesn't require a source control or composer.

:::info
Vito doesn't provide a file manager, and you need to upload your files by connecting to the server via SSH.
:::

### Laravel

Laravel site type is a subtype of the PHP site type. With this separation, We will be able to provide more Laravel
specified features like Artisan commands in the future.

### Node.js with NPM

You can deploy any Node.js application that uses NPM as a package manager with this site type including backend and frontend apps like Next.js, Nuxt.js, Express.js, etc.

Deploying a Node.js app requires `build` and `start` scripts to be available in the `package.json` file.

### WordPress

Vito installs WordPress easily by just submitting a form. You don't need to download WordPress and upload it to your
server.

:::info
Additional to the PHP site type requirements, You also need to have Mysql service in
the [Services](../servers/services) section.
:::

### PHPMyAdmin

Vito supports PHPMyAdmin installation out of the box. It will install PHPMyAdmin and connect it your Mysql service.

### Load Balancer

You can use your server as a load balancer by adding a new site with the Load Balancer site type.

Read more about [Load Balancer](./load-balancer.md).

## Composer

Vito handles the composer installation for you during the site creation process.

If you want to run `composer install` after cloning the repository click the checkbox.

If you check this, It will run the following command on the installation process after cloning the repository.

```sh
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev
```
