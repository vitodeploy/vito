# Modern Deployment

:::warning
Modern Deployment is in Beta. Please use it with caution and report any issues you find.
:::

## Introduction

Modern deployment is a site feature that basically enables you to deploy your sites with Zero Downtime and also lets you to rollback to any previous releases. Modern deployment changes your site's high level structure to enable zero downtime deployments and rollbacks.

## How Modern Deployment Works

Enabling Modern Deployment changes your site's structure to the following:

Vito deploys your app under `/home/your-user/domain.com` directory where `your-user` is the isolated user you specified when creating the site.

After enabling modern deployment it will be like the following:

```text
- current -> releases/initial
- releases
  - initial
  - 20230914120000
  - 20230913120000
- source
```

Vito moves the current existing code into the `source` directory and then creates the releases directory and copies the source into the `releases/initial` directory.

Then it links the shared resources like `storage` and `.env` that you've provided during enabling modern deployment from the `source` directory to the `initial` release directory.

And lastly it points the `current` symlink to the `releases/initial` directory.

Then it updates the VHost configuration to use the `current` symlink as the document root.

The next releases will simply clone the repo into the new release directory which is a timestamp under the `releases` directory, and then runs the Build script and PreFlight script and after they are done, It links the `current` symlink to the new release directory.

For the rollbacks, they simply point the `current` symlink to the chosen release directory.

## Supported site types

Modern deployment currently supports Laravel sites only.

## Enabling Modern Deployment

After creating a site, Navigate to the Site Features menu and there you can find Modern Deployment feature. Click on Action and enable it.

When enabling modern deployment, you need to provide the following information:

- Shared resources: These are the resources that will be shared between all releases. You can provide multiple resources separated by comma. For example: `storage,.env,database/database.sqlite`
- History: This is the number of releases that will be kept in the server. For example if you set it to 5, Vito will keep the last 5 releases and delete the older ones. This is useful to save disk space.

:::warning
During Beta, Enabling modern deployment is only recommended for fresh websites than a live production website.
:::

:::warning
If you have queue workers or cronjobs or commands set up for your site, You need to update the command of your workers/cronjobs/commands if they are pointing to the old root path of your domain. You need to change the root path to point to the `current` symlink. For example, if your domain is `domain.com` and your user is `myuser`, and your workers/cronjobs/commands has the old root path `/home/myuser/domain.com`, you need to change it to `/home/myuser/domain.com/current`.
:::

:::danger
Modern deployment is not designed for Octane like setups. So if you're using Octane, Avoid enabling modern deployment.
:::

In case if your website becomes unaccessible after enabling modern deployment, You can try setting the Build Script and Pre Flight script and then trigger a deployment with having Modern Deployment enabled.

If that didn't help, You can disable the modern deployment and your website should be back to the previous state.

## Zero Downtime Deployment

All deployments after enabling modern deployment will be zero downtime. In order to deploy, You will need to provide 2 scripts:

**Build Script:** This script will be run after cloning the repo into the new release directory. You can use this script to install dependencies, compile assets, like `composer install && npm install && npm run build`.

:::info
If your `npm run build` requires the `.env` to be present, you need to run it in the Pre Flight script instead of Build script because the shared resources are linked after the Build script.
:::

**Pre Flight Script:** This script will be run after build script and after linking the shared resources but before pointing the `current` symlink to the new release. You can use this script to run database migrations, clear caches, etc. like `php artisan migrate --force && php artisan config:cache`.

## Rollbacks

Rollbacks are basically switching the releases. You can switch the current release to a version before or after by selecting the release and clicking the Rollback button.

The Rollback action will simply point the `current` symlink to the selected release directory.

:::info
Vito won't execute any scripts during rollbacks. So if you need to run any scripts after rollback, you can do it via Site Commands or [Scripts](../scripts.md).
:::

## Removing releases

Depending on the history you've set, Vito will automatically remove the older releases during deployments.

You have the option to manually remove any release by selecting the release and clicking the Delete button.

## Modern Deployment Configuration

You can change the modern deployment configuration by clicking the Configuration action in the site features and change the deployment history count to keep or change the shared resources.

## Disabling Modern Deployment

Disabling modern deployment will revert your site structure to the previous one.

When disabling modern deployment, Vito will take the last active release and move it to the same structure as before enabling modern deployment.

:::warning
Your website might face a little downtime during disabling modern deployment depending on the size of your website.
:::
