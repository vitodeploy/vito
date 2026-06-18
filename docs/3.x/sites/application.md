# Application

## Introduction

In the Application page you can manage your app and deployments.

## Branch

You can change the branch of your cloned repository

## Deployment Script

This is a script which will be executed on your application server and in the site's path each time you press the `Deploy` button.

:::info
Your website's path is `/home/vito/YOUR-DOMAIN`
:::

Vito also exports some variables when running your deployment script, and you can use them during the deployment.

Here are the supported variables:

```
SITE_PATH=[path to the website]
DOMAIN=[domain name]
BRANCH=[branch name]
REPOSITORY=[repository]
COMMIT_ID=[commit id when deploying]
PHP_VERSION=[the php version that your site running]
PHP_PATH=[path to the php your site running]
```

The variables are bash script variables and you can use them just like a normal bash script variable.

here is an example:

```shell
echo "Deploying $DOMAIN to $SITE_PATH"
```

Example deployment script for a Laravel application:

```bash
cd $SITE_PATH

php artisan down

git pull origin $BRANCH

composer install --no-interaction --prefer-dist --optimize-autoloader
php artisan migrate --force

php artisan optimize:clear
php artisan optimize

sudo service supervisor restart

php artisan up

echo "✅ Deployment completed successfully!"
```

## Environment variables

You can update `.env` file of your application using `Update .env` button.

By default, Vito will read the `.env` file from your site's root directory. However, you can change the path to the `.env` file when updating it via Vito.

## Deploy

This button appears when there is a deployment script! So you need to first write your deployment script and then this
button will appear, and you will be able to click on it an execute the deployment script on the server.

## Auto Deployment

You can enable auto deployment for your application by setting up git hooks.

Vito will handle the git hooks setup for you and you just need to click a button to enable it.

:::warning
You need to have a deployment script to enable auto deployment.
:::

:::warning
Since source control providers need to send a request to your server, you need to have vito accessible in the Internet.
This feature
cannot work when you use Vito locally.
:::
