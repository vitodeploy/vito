# Settings

## Introduction

In the Settings page, you can manage your website settings like its PHP version, Aliases, Custom Vhost and more.

## Change PHP Version

You can change the PHP version of each website in their Settings page.

Make sure that the PHP version you want to use is already installed in the [PHP](../servers/php#install-and-uninstall)
page.

## Change branch

You can change the branch of your cloned repository in the Settings page.

## Change source control

You can change the source control of your cloned repository in the Settings page.

## Update Aliases

You can add/remove site aliases. It will update the aliases on your site's nginx vhost configuration.

:::info
This will only override the #[port] block in your vhost configuration. The rest will remain unchanged.
:::

:::info
Updating the aliases will reload the Nginx service. If you need a restart, you need to go to [services](../servers/services.md) and restart nginx manually.
:::

## Update VHost

It is possible to customize the virtual host configuration of your webserver (Nginx). However, it is not recommended to
do so unless you know what you are doing.

Vito will show you the current configuration of the site, and you can modify it as you wish.

Vito now uses custom blocks in the VHost configuration, to enable you to add custom configurations without your configuration being overridden by Vito.

:::tip
You can use the custom blocks when you write a plugin for Vito.
:::

Here is an example of Nginx vhost configuration:

```nginx
#[header]
#[force-ssl]
#[/force-ssl]

#[laravel-octane-map]
map $http_upgrade $connection_upgrade {
    default upgrade;
    ''      close;
}
#[/laravel-octane-map]

#[/header]

server {
    #[main]
    #[port]
    listen 80;
    listen [::]:80;
    #[/port]

    #[core]
    server_name yourdomain.com ;
    root /home/vito/yourdomain.com/public;
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    charset utf-8;
    access_log off;
    error_log  /var/log/nginx/yourdomain.com-error.log error;
    location ~ /\.(?!well-known).* {
        deny all;
    }
    #[/core]

    #[laravel-octane]
    index index.php index.html;
    error_page 404 /index.php;
    location /index.php {
        try_files /not_exists @octane;
    }
    location / {
        try_files $uri $uri/ @octane;
    }
    location @octane {
        set $suffix "";
        if ($uri = /index.php) {
            set $suffix ?$query_string;
        }
        proxy_http_version 1.1;
        proxy_set_header Host $http_host;
        proxy_set_header Scheme $scheme;
        proxy_set_header SERVER_PORT $server_port;
        proxy_set_header REMOTE_ADDR $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection $connection_upgrade;
        proxy_pass http://127.0.0.1:8000$suffix;
    }
    #[/laravel-octane]

    #[redirects]
    #[/redirects]

    #[/main]
}

#[footer]
#[/footer]
```

:::warning
Most of the vhost file's blocks will get reset if you generate or modify SSLs, Aliases, or create/delete site redirects.
:::

## Delete

You can delete the website from your server.

This will delete the files of your website and the webserver configurations related to your website from the Server.
