# Source Controls

## Introduction

Vito uses source controls to pull your source codes for the websites you install. It also uses to set up hooks for auto
deployments.

## Supported Providers

- GitHub (personal access token)
- GitHub App
- Gitlab (Cloud and Self-hosted)
- Bitbucket

## GitHub App

In v4.x you can connect GitHub through a **GitHub App** instead of a personal access token. A GitHub
App is a more secure way to connect, authenticating with short-lived, per-repository tokens rather
than a long-lived token tied to your account.

Deployments through a GitHub App also **no longer require deployment keys**. Instead of adding an SSH
deploy key to each repository, Vito injects a short-lived (1 hour), per-repository token at deploy
time, which exists only in the environment during the deployment and is never written to disk.

You register the app once for your Vito instance from the admin area. See
[Admin > GitHub App](/docs/4.x/admin/github-app) for the setup steps.

Once the app is installed on a GitHub organization or account, each connected organization appears
automatically as a source control record here. These records behave differently from the other
providers:

- They are created as **global** records (available to all projects).
- You can **edit** a record to make it non-global (scoped to a single project).
- You **cannot delete** them from within Vito. They are managed through GitHub instead.
- When you **uninstall** the app from an organization on GitHub, its record is removed from this list
  automatically.

:::info
GitHub App installations are kept in sync with GitHub via webhooks, with a periodic background sync
as a fallback. If your Vito instance is not publicly reachable, changes are picked up on the sync or
when you trigger it manually. See [Admin > GitHub App](/docs/4.x/admin/github-app#keeping-installations-in-sync).
:::

## Required API Permissions

Vito connects to the source control providers via their APIs. To connect to the source control providers the following
information is required:

### Github

Generate a personal access token on GitHub settings of your account and give it full repository control and git hook
admin.

:::info
If you're using Github's fine grained personl tokens, Make sure you have Read & Write on `Administration`, `Contents` and `Webhooks` scopes.
:::

### Gitlab

Generate a personal access token on your Gitlab profile and give it `write_repository, api` permissions.

### Bitbucket (Deprecated)

:::warning
The Bitbucket source control provider is deprecated due to deprecation of App Passwords by Bitbucket. Use [Bitbucket V2](#bitbucket-v2) instead.
:::

Create an App Password on your Bitbucket account and give it repository and webhook admin permissions.

Vito will ask for username and password to connect to Bitbucket.

First you need to log into your Bitbucket account and navigate to [App Passwords](https://bitbucket.org/account/settings/app-passwords/) under the Personal Settings and then create a password and copy.

The username field when connecting to Vito will be your account's username and the password will be the password you created using App Passwords.

### Bitbucket V2

This is a new source control provider for Bitbucket which is using Oauth Consumers of Bitbucket.

To connect, Navigate to your Bitbucket workspace and then `Oauth Consumers` and add a new consumer.

It is important to provide a callback URL (use your Vito instance's URL) and make sure you check the `This is a private consumer` checkbox.

Then check the following permissions:

- Account: Read
- Webhooks: Read and Write
- Repositories: Read and Write

Then create the consumer and use the `Key` and `Secret` to connect to it in Vito.

## Scope

Source controls can be created under a specific project or globally.

If you create a source control under a project, it will only be available for that project.

If you create a source control globally, it will be available for all projects.

The reason of this feature is when you add a new user to VitoDeploy, you can control which source controls they can
access.

:::info
In any scope, only you will have access to see or use that source control and other users of the project will not be able to see or use it.
:::
