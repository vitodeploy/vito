# Settings

In the Settings page of each server, You can view/modify the information of the server such as

- Name
- SSH IP
- SSH Port

If you change the IP or Port, Vito will try to reconnect to the server via new IP and Port.

## Update/Upgrade Server

Vito enables you to check for new updates on your OS and update them manually via clicking the Update button.

On the server settings you can see how many updates are available for your server. In order to do so, You just need to click the `Check` button to get the number of available updates.

Then to update/upgrade, You can simply click on the `Update` button.

This will change your server's status to `UPDATING` and no actions will be allowed to do to your server via VitoDeploy until the update is done.

:::info
If you still see some available updates after the update is done, It is about the packages that are kept back during the update.
[Read more here](https://ubuntu.com/server/docs/about-apt-upgrade-and-phased-updates)
:::

:::info
If you have set up the [notification channels](../settings/notification-channels), Vito will send you a notification if the update fails
:::

## Restart

You can restart the server by clicking the `Restart` button.

## Delete

You have the option of deleting a server.

Deleting a server will erase it from your Vito Instance's database and if the server was created on a supported server providers (Not custom), It will try to delete the server on the provider via their APIs.

:::danger
It is possible that Vito cannot reach to the Server Provider to delete the instance. If that happens you will get an email (If you have configured the [Email](../getting-started/configuration.md#email)) saying that Vito couldn't delete the server on the provider.
:::
