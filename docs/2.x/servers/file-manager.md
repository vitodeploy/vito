# File Manager

## Introduction

Vito has a built-in file manager that allows you to manage your files and directories directly from the the server's dashboard.

Since Vito is a remote server management tool, The file manager will interact with your server's files remotely via SSH and SFTP.

For example, If you upload a file using the file manager, It will first upload the file to your Vito instance and then from there it will upload the file to your server.

## File Browser

The file manager has a file browser which you can navigate through your server's files and directories by clicking on the directories. Vito allows you to interact with the files with any of the existing users that Vito has created on your server. For example if your site is [isolated](../sites/isolation.md) then you can only interact with the files using the isolated user.

## Upload Files

You can upload files by clicking the `Upload` button on the top right corner of the file manager.

:::info
Vito will first upload the file to your Vito instance and then from there it will upload the file to your server.
:::

## Download Files

You can download files by clicking the `Download` icon on the right side of each file.

:::info
Vito will first download the file to your Vito instance and then from there it will download the file to your local machine.
:::

## Create New File

You can create a new file by clicking the `Create File` button on the top right corner of the file manager.

## Create New Directory

You can create a new directory by clicking the `Create Directory` button on the top right corner of the file manager.

## Edit Files

You can edit files by clicking the `Edit` icon on the right side of each file.

This will open a code editor where you can edit the file.

## Delete Files

You can delete files by clicking the `Delete` icon on the right side of each file.

## Zip Extract

Vito's file manager allows you to extract zip files by clicking the `Extract` icon on the right side of each zip file.