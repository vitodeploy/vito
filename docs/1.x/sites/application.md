# Application

In the Application page you can manage your app and deployments.

## Branch

You can change the branch of your cloned repository

## Deployment Script

This is a script which will be executed on your application server each time you press the `Deploy` button.

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

## .env

This is the `.env` file of your PHP app (In this case Laravel) which you've installed on your server.

:::warning
Vito doesn't read the `.env` file in the beginning and you need to initiate the file from this page.
:::

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
Since source control providers need to send a request to your server, you need to have vito on a VPS. This feature
cannot work when you use Vito locally.
:::
