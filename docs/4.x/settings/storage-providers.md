# Storage Providers

## Introduction

Vito supports multiple storage providers to store your backups, files, and other data.

## Supported Providers

- Amazon S3 (and S3-compatible providers such as Wasabi)
- FTP(s)
- SFTP
- Dropbox
- Local

### Amazon S3

Vito supports Amazon S3 as a storage driver. To use it you need a bucket on your S3 account and the
following fields:

| Field | Required | Description |
| --- | --- | --- |
| API URL | No | The S3 endpoint URL. **Leave empty for Amazon S3** — Vito derives it from the region (`https://s3.{region}.amazonaws.com`). Set this only for S3-compatible providers (see below). |
| Access Key | Yes | The access key of your S3 account ([How?](https://docs.aws.amazon.com/AmazonS3/latest/userguide/configuring-bucket-key.html)) |
| Secret Key | Yes | The secret key of your S3 account |
| Region | Yes | The bucket's region **as a bare region code**, e.g. `eu-central-1`. See the warning below. |
| Bucket Name | Yes | The name of the bucket |
| Path | No | The path within the bucket where files are stored |

:::warning
**Region must be a bare region code** (for example `us-east-1`, `eu-central-1`), **not** a full
hostname. Entering a hostname such as `s3.eu-central-1.amazonaws.com` results in the error
`Invalid region: region was not a valid DNS name`. The hostname belongs in the **API URL** field, not
the **Region** field.
:::

For more info on how to create a bucket see the
[Amazon S3 Documentation](https://docs.aws.amazon.com/AmazonS3/latest/userguide/creating-bucket.html).

#### S3-compatible providers (Wasabi, etc.)

Any S3-compatible object storage — such as [Wasabi](https://wasabi.com/), Backblaze B2, MinIO, or
DigitalOcean Spaces — uses the same **Amazon S3** provider. There is no longer a separate Wasabi
provider; just point the **API URL** at your provider's endpoint and keep the **Region** as the bare
region code.

For example, to connect a Wasabi bucket in the `eu-central-2` region:

| Field | Value |
| --- | --- |
| API URL | `https://s3.eu-central-2.wasabisys.com` |
| Region | `eu-central-2` |

:::warning
Include the `https://` scheme in the **API URL** — the value is passed to the S3 client as the
endpoint verbatim.
:::

For Wasabi specifically, follow [their documentation](https://docs.wasabi.com/docs/creating-a-user-account-and-access-key)
to create an access key, and the [service URLs reference](https://docs.wasabi.com/docs/what-are-the-service-urls-for-wasabis-different-storage-regions)
to find the correct endpoint and region code for your bucket.

### FTP(s)

To connect to FTP(s) you need a separate FTP(s) server and its connection info: **Host**, **Port**,
**Username**, **Password**, and the **Path** files are stored under. Enable **Use SSL** for FTPS, and
toggle **Use Passive Mode** to match your server (passive is on by default).

### SFTP

SFTP connects over SSH. Provide the **Host**, **Port** (default `22`), **Username**, **Password**, and
the **Path** files are stored under.

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
