# Vito Settings

## Introduction

Here you can manage some of the settings of your Vito instance.

## Export

You can export your Vito instance settings to a file. This file can be used to import the settings to another Vito
instance.

The exported file is a zip file which contains the following data:

- The Vito database (`database.sqlite`)
- The instance's environment file (`.env`)
- The instance's SSH key pair (`ssh-public.key` and `ssh-private.pem`)
- The server key pairs
- The stored server logs

For more detail, see [Export & Import](../admin/export-import.md).

## Import

You can import the exported settings zip file back to Vito. This will restore your Vito instance to the state it was
when you exported it.

:::danger
Importing replaces the current database, environment file, SSH keys, key pairs, and server logs. It is
a full restore, not a merge.
:::