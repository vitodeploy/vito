# Source Controls

## Introduction

Vito uses source controls to pull your source codes for the websites you install. It also uses to set up hooks for auto
deployments.

## Supported Providers

- GitHub
- Gitlab (Cloud and Self-hosted)
- Bitbucket

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

Generate a personal access token on your Gitlab profile and give it repository and webhook admin.

### Bitbucket

Create an App Password on your Bitbucket account and give it repository and webhook admin permissions.

Vito will ask for username and password to connect to Bitbucket.

First you need to log into your Bitbucket account and navigate to [App Passwords](https://bitbucket.org/account/settings/app-passwords/) under the Personal Settings and then create a password and copy.

The username field when connecting to Vito will be your account's username and the password will be the password you created using App Passwords.

## Scope

Source controls can be created under a specific project or globally.

If you create a source control under a project, it will only be available for that project.

If you create a source control globally, it will be available for all projects.

The reason of this feature is when you add a new user to VitoDeploy, you can control which source controls they can
access.
