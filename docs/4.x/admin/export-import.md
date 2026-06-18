# Export & Import

## Introduction

Vito can export everything it has stored into a single backup file, and import it back into another
instance. This is useful for migrating to a new Vito instance, or for keeping a backup of your
current one.

## Export

From **Admin > Export & Import**, click **Export** to download a backup. The file is a zip named
`vito-backup-YYYY-MM-DD.zip` and contains:

- The Vito database (`database.sqlite`), which holds your servers, sites, projects, users, logs, and
  all other records.
- The instance's SSH key pair (`ssh-public.key` and `ssh-private.pem`).
- The server key pairs.
- The stored server logs.

:::warning
The backup contains your instance's private SSH key and the full database. Store it somewhere secure
and treat it as sensitive.
:::

## Import

Click **Import** and upload a previously exported zip file to restore it.

:::danger
Importing **replaces** the current database, SSH keys, key pairs, and server logs with the contents
of the backup. It is a full restore, not a merge. Only import into a fresh instance, or one whose
current data you are intentionally overwriting.
:::

:::info
Because the instance's SSH key pair is part of the backup, an imported instance keeps the same keys
and can continue to connect to your existing servers without re-adding them.
:::
