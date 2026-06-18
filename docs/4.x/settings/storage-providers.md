# Storage Providers

## Introduction

Vito supports multiple storage providers to store your backups, files, and other data.

## Supported Providers

- Amazon S3
- FTP(s)
- Wassabi (Depreciated)
- Dropbox
- Local

### Amazon S3

Vito supports Amazon S3 as storage driver. To use it, You need to have a bucket on your S3 account and provide the
following info:

- Path: The path where the files will be stored in the bucket
- Key: The Access Key of your S3 account ([How?](https://docs.aws.amazon.com/AmazonS3/latest/userguide/configuring-bucket-key.html))
- Secret: The Secret Key of your S3 account
- Region: The region of your S3 bucket
- Bucket: The name of the bucket

For more info on how to create a bucket you can visit [Amazon S3 Documentation](https://docs.aws.amazon.com/AmazonS3/latest/userguide/creating-bucket.html)

### FTP(s)

To connect to FTP(s) you need to have a separate FTP(s) server with its connection info like HOST, PORT, USERNAME, and
PASSWORD

### Wassabi (Depreciated)

[Wassabi](https://wasabi.com/) is a third-party storage provider and S3 compatible storage provider.

Steps to connect are the same as Amazon S3. But to create a key, you need to follow this [documentation](https://docs.wasabi.com/docs/creating-a-user-account-and-access-key)

### Dropbox

Dropbox connects through **OAuth** with offline access. Dropbox access tokens are short-lived and
expire after 4 hours, so Vito stores a long-lived **refresh token** during authorization and uses it
to mint a fresh access token automatically whenever a backup runs — there is nothing to rotate by
hand.

:::warning
The previous flow, where you pasted a single generated access token, is no longer supported — those
tokens stop working after 4 hours. If you connected Dropbox this way before, reconnect it using the
steps below.
:::

To connect Dropbox:

1. Open the [Dropbox App Console](https://www.dropbox.com/developers/apps) and create an app with
   **Scoped access** and access to your Dropbox.
2. On the app's **Permissions** tab, enable the permissions listed below and save.
3. On the app's **Settings** tab, add Vito's redirect URI under **OAuth 2 → Redirect URIs**. The
   exact URI is shown in Vito's "Connect to storage provider" dialog when you select Dropbox, and
   looks like:
   `https://your-vito-host/settings/storage-providers/dropbox/callback`
4. Copy the **App key** and **App secret** from the same Settings tab.
5. In Vito, choose **Dropbox** as the provider, enter the **App key** and **App secret**, then click
   **Connect**. You will be redirected to Dropbox to authorize access; once you approve, Dropbox
   returns you to Vito and the provider is connected.

:::info
Using Dropbox requires the following permissions on your Dropbox app:

- `files.metadata.read`
- `files.metadata.write`
- `files.content.read`
- `files.content.write`
:::

:::info
Authorization happens in your own browser, so Vito does **not** need to be publicly accessible — it
just needs a domain. Dropbox requires the redirect URI to use **HTTPS** for any domain (a local
`https://your-vito.test` is fine), with one exception: plain HTTP is only allowed when the host is
`localhost`. The redirect URI you register must match the one Vito uses exactly.
:::

### Local

Local storage means that the server itself will be used as the storage. For example if you have a server managed by Vito
and want to back up the databases, The backup files will be stored in the same server that the database exists.

:::warning
To use this driver, You need to provide a path and the `vito` user must have write access to that path
:::

## Scope

Storage providers can be created under a specific project or globally.

If you create a storage provider under a project, it will only be available for that project.

If you create a storage provider globally, it will be available for all projects.

The reason of this feature is when you add a new user to VitoDeploy, you can control which storage provider they can
access.

:::info
In any scope, only you will have access to see or use that provider and other users of the project will not be able to see or use it.
:::
