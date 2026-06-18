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

To connect to Dropbox you need to create an app on your Dropbox developer generate a token for that and use that token
to connect your VitoDeploy instance to Dropbox.

:::info
Using Dropbox requires the following permissions on your Dropbox API Key:

- `files.metadata.read`
- `files.metadata.write`
- `files.content.read`
- `files.content.write`
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
