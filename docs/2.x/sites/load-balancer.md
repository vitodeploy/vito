# Load Balancer

## Introduction

Vito uses Nginx to create load balancer to distribute incoming traffic across multiple servers.

You can add multiple servers to the load balancer and Vito will automatically update the Nginx configuration file.

:::warning
**Before setting up a load balancer:**

- You need at least 3 servers. One for the load balancer and Two for the backend servers.
- All the servers must be in the same region and private network.
- Vito doesn't create private networks, So you need to create it manually on your server provider and assign local IP
  addresses to the servers.
- After assigning local IP addresses, you need to set the local IPs in the server settings for each server.
:::

## How to set up Load Balancer

Since a load balancer is a virtual host in the webserver, You need to have
a [webserver service (Nginx)](../servers/services.md#install) installed on your
server.

For Vito, a load balancer is a site type because it's a virtual host in the webserver.

To create a load balancer, follow these steps:

1. Go to the Sites on your server.
2. Click on the `Create a Site` button.
3. Select `Load Balancer` from the site types.
4. Fill in the required fields.
5. Click on the `Create Site` button.

## Balancing Methods

When creating a load balancer, you need to specify the balancing method. This can be changed later.

Vito supports the following balancing methods:

- Round Robin
- Least Connections
- IP Hash

## Load Balancer Servers

After the load balancer has been created, you can add or remove servers from the load balancer.

**Balancing Method:**

The balancing method is used to distribute the load between the servers.

**Server:**

There is a dropdown to select the server you want to add to the load balancer.

:::warning
Make sure the server has a local IP address to be shown in the dropdown.
:::

**Port:**

Enter the port number that the app is running on the server.

**Weight:**

The weight is used to distribute the load between the servers. The higher the weight, the more requests the server will

**Backup:**

If the server is a backup server, It will only be used when all the other servers are down.

