# Create server

## Introduction

VitoDeploy allows you to create servers on your connected server providers with ease. You can create servers with a few
clicks and deploy your applications on them.

## How to Create a Server

VitoDeploy makes server creation easy and fast. Server provisioning has gone lighter and faster over the past few versions of VitoDeploy.

Vito now provisions bare servers and focuses on the essential tools to install and configure your server.

:::info
You will need to install services like Nginx, MySQL, Redis, etc. on your server after the server installation is done.
:::

### Select a server provider

Vito allows you to create servers on the top cloud providers by default or a custom server by having root access to it.

:::info
To add more server providers, You can develop a plugin for it.

[Plugins Documentation](../plugins.md)
:::

If you select the custom server provider, you will need to add your Vito instance's SSH public key to your server to allow Vito to connect to it.

You can see a command like bellow, make sure to run it as the `root` user on your server before you start creating the server:

```sh
mkdir -p /root/.ssh && touch /root/.ssh/authorized_keys && echo 'ssh-rsa XXXXXXXXXXXXXXXXXXXXXX' >> /root/.ssh/authorized_keys
```

### Fill in the form

If you've selected a cloud server provider, you will need to fill in the form with the required information like region, plan etc.

The rest of the fields are:

- **Server Name**: The name of your server (must be unique among your current project).
- **Operating System**: The operating system of your server (Ubuntu 22.04 or 24.04).
- **SSH IP**: The IP address of your server (if you are using a custom server provider).
- **SSH Port**: The SSH port of your server (if you are using a custom server provider).

### Select services

After completing the form, you can select the services you want to install on your server. You can also install the services later after the installation is done.

After selecting the services, You can save the selected services as server templates to use them later when creating a new server.

This comes handy when you are rapidly creating multiple servers with the same services.

You can also update or delete each server template from the server create form.

### Create the server

After filling in the form, you can click on the `Create Server` button to create your server.

The installation can take a few minutes. You will see the progress of the installation.

You will also be able to see the logs of the installation in real-time.

## Server overview

After the server is created, you will be redirected to the server overview page.

This page will show you and overview of your server like its resource usage and recent activity logs.

## Installation failure

Server installation can fail for various reasons like network issues, server provider issues, etc.

You can check the logs of the installation to see what went wrong.

If you don't see any logs, you can check the Vito logs (left bottom of the dashboard) to see if there is any error related to Vito itself.

:::info
You can ask for community support in the [VitoDeploy Discord Server](https://discord.com/invite/uZeeHZZnm5) if you need help with the installation.

Or you can open an issue on the [VitoDeploy GitHub Repository](https://github.com/vitodeploy/vito)
:::
