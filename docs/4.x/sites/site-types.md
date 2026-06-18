# Site Types

## Introduction

Vito is built to support deploying PHP applications and currently, it supports any PHP applications. But it has site
types for provide more features out of the box for some specific PHP applications.

## Supported Site Types

- PHP (Any other PHP applications)
- Blank PHP
- Laravel
- Node
- Bun
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
specified features like Artisan commands in the future.   During site creation of a Laravel site, you are asked to
pick a pacakge manager, by default, Laravel uses NPM (Node), but you can select other package managers such as Bun,
pnpm or Yarn during site creation.

Additional tooling can always be installed via the [Site Tooling](./site-tooling.md) menu post site creation if needed. 

### Node & Bun

You can deploy any Node.js or Bun application with these site types including backend and frontend apps like Next.js, 
Nuxt.js, Express.js, etc.  [Site Tooling](./site-tooling.md) will be enabled based on the selected versions of tools selected during site
creation automatically.  You are free to install other tooling as needed. 

These run as reverse proxy sites. Once deployed, you can change the port, environment variables, build
script, and start command, and manage the application worker, from the
[Application](./application#reverse-proxy-sites) page. 


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
