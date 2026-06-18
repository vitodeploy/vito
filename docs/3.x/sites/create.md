# Create site

## Introduction

In this section, you can create a new site on your server. Sites are accessible in the main menu and every server's own
menu.

Vito allows you to see all the sites in a project (main menu) or sites in a specific server (server's own menu).

Also, you can easily navigate to projects, servers and sites from the top navigation bar.

## Create a new site

You can create a new site using the sites section on top navigation bar or navigating to the sites section in the
server's own menu.

To create a new site, you need to provide the following information:

### Select a site type

Select a site type that your application is based on. Read more about [site types](./site-types.md).

:::info
You can develop a plugin to add more site types to VitoDeploy.

[Plugins Documentation](../plugins.md#register-site-types)
:::

### Domain

The domain of your site. You can use a custom domain or a subdomain. Make sure to add the domain to your DNS provider
and point it to your server's IP address.

### Aliases

You can add aliases to your site. Aliases are additional domains that point to the same site. For example, you can add
`www.example.com` as an alias for `example.com`.

### Site type fields

Every site type has its own fields that you need to fill in. For example, if you select the WordPress site type, you
will need to provide the WordPress admin username and password.

### User

Every site requires a unique system user. VitoDeploy creates a dedicated user on the server for each site to ensure full
isolation between sites.

Read more about [isolation](./isolation.md).

