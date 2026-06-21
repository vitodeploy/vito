# Securing VitoDeploy

## Install SSL on Vito instance

If you've installed VitoDeploy on a VPS, By default your Vito instance will be accessible via your server's IP address which is not secure.

To secure it you need to attach a domain to it and get an SSL certificate for it.

### Attach a domain

Create an A record on your domain's DNS manager and point it to your Vito server's IP address.

:::warning
If you are using Cloudflare make sure you have the cloud proxy off at this stage!
:::

Open `/etc/nginx/sites-available/vito` with an editor on your terminal like `nano`:

```sh
sudo nano /etc/nginx/sites-available/vito
```

Edit it like the following and add your domain to it:

```
server {
    ...
    server_name _ YOUR_DOMAIN_GOES_HERE;
    ...
}
```

Now run the following command to restart the webserver:

```sh
sudo service nginx restart
```

Make sure your webserver is not broken:

```sh
sudo service nginx status
```

It should have something like this:

```
● nginx.service - A high performance web server and a reverse proxy server
     Loaded: loaded (/lib/systemd/system/nginx.service; enabled; vendor preset: enabled)
     Active: active (running) since Wed 2024-04-10 11:11:32 UTC; 3 days ago
       Docs: man:nginx(8)
   Main PID: 172473 (nginx)
...
```

Now open your domain to make sure it is ok and can be opened.

### Get SSL

Now you need to run the following command to generate the SSL

```sh
sudo certbot --force-renewal --nginx --noninteractive --agree-tos --cert-name YOUR_DOMAIN -m YOUR_EMAIL -d YOUR_DOMAIN --verbose
```

Make sure to replace the placeholders:

YOUR_DOMAIN: Your domain address like example.com

YOUR_EMAIL: Your email address to be provided to letsencrypt

:::info
If you are using Cloudflare, Now you can enable the cloud proxy if you want. Keep in mind that you might need to enable Full SSL encryption option on Cloudflare
:::
