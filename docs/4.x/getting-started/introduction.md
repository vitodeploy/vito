# Introduction

Vito is a powerful, open-source server management tool designed to simplify the deployment and management of web applications. With first-class support for PHP and Laravel, as well as Node.js and Bun applications, it provides a user-friendly interface for developers and system administrators to automate server provisioning, application deployment, and maintenance tasks.

Here are a list of features that Vito provides:

## Server Provisioning

Vito allows you to quickly set up and configure servers with the necessary software stack for your web application.

You can create servers on the top cloud providers like AWS, DigitalOcean, and Linode, etc. with just a few clicks. Vito supports various server configurations, including different operating systems and software stacks.

## Database

You can easily install databases like MySQL, PostgreSQL, and MariaDB, and manage them through the Vito interface.

Features available for the databases:

- Create and manage databases
- Create and manage users
- Database backups

## Application

Vito simplifies the deployment of web applications by automating the process of creating virtual
hosts, configuring web servers, cloning repositories, and setting up environment variables. It
supports PHP and Laravel sites, as well as Node.js and Bun applications served via reverse proxy.

Features available for the websites:

- Deployments, including resuming failed installations
- Hosted domains (multiple alias and redirect domains per site, with DNS validation)
- SSL, including wildcard certificates
- HTTP basic authentication
- Site Tooling (per-site runtimes and package managers)
- Supervisor Workers
- Commands
- Environment Variables

## Firewall

Vito allows you to manage your server's firewall rules easily. You can add, remove, and modify rules to control incoming and outgoing traffic.

## Programming Languages

You can install and manage multiple versions of PHP on your server, allowing you to run applications
that require different versions. JavaScript runtimes and package managers such as Node.js, Bun, Yarn,
and pnpm are managed per site through [Site Tooling](/docs/4.x/sites/site-tooling).

## Cron Jobs

Vito provides a simple interface to manage cron jobs on your server. You can create, edit, and delete cron jobs without needing to manually edit the crontab file.

## Supervisor Workers

Vito allows you to manage background processes using Supervisor. You can create and manage Supervisor workers to run tasks in the background, ensuring that your web applications remain responsive.

## Services

Vito is a service oriented server management tool, which means you most of the features are available as services. You can install and manage services like Redis, Monitoring, Databases, and more.

## Monitoring

Vito provides monitoring capabilities to keep track of your server's performance and resource usage. You can monitor CPU, memory, disk usage to ensure your server is running smoothly.

## Console

Vito includes a full, interactive terminal for your server, right in the browser. It opens a live SSH
shell so you can run commands, install packages, and use interactive programs without opening your own
SSH client.

## Plugins

Vito supports plugins, allowing you to extend its functionality. You can install official and community-supported plugins to add new features or modify existing ones.

## Export and Import

Vito allows you to export and import your server configurations. This feature is useful for backing up your settings or migrating your Vito instance to another server.

## Workflows and Automations

You can automate almost any workflows using Vito's Workflows feature. Create custom workflows to automate repetitive tasks, such as deployments, backups, and maintenance.

## Domains and DNS Management

Vito integrates with your DNS provider to enable you to manage your domain's DNS records directly from the Vito interface. You can add, modify, and delete DNS records for your domains without needing to log in to your DNS provider's dashboard.

## API Access

Vito provides a RESTful API that allows you to programmatically interact with your Vito instance. You can use the API to automate tasks, integrate with other tools, and manage your servers and applications.
