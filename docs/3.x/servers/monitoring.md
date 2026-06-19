# Server Monitoring

## Introduction

VitoDeploy enables you to monitor your servers' resource usages like CPU Load, Memory, and Disk usage.

Vito offers 2 ways of monitoring servers.

## Remote Monitor

If you chose to use the Remote Monitor, Vito will check your servers every minute to extract their resource usages.

To install Remote Monitor you just need to go to the Services and hit the Install button on the Remote Monitor service
under the Supported Services section.

After the installation was successful, You can navigate to the Metrics page and view the statistics.

Keep in mind that it will take a couple of minutes to collect enough data to visualize them.

## Vito Agent

To enable Vito to monitor your servers you need to install the VitoAgent service first on your server.

[VitoAgent](https://github.com/vitodeploy/agent) is a program written in Golang that collects the resource usages and
sends them to your Vito instance.

:::warning
Since VitoAgent sends these information through internet, It won't be possible to use monitoring if you are using Vito
on your local computer!
:::

To install VitoAgent simply navigate to the [Services](./services) and click on the Install button for `VitoAgent`
service.

You can uninstall VitoAgent anytime through the `Services` page.

## Monitoring

After you installed VitoAgent, The `Metrics` item will be visible on the sidebar and you can navigate there and wait for
the data coming from your server.

:::info
VitoAgent sends information to Vito instance once per minute.
:::

## Data Retention

In the `Metrics` page you can modify the data retention time so Vito will delete older data to keep your Vito's database
faster.
