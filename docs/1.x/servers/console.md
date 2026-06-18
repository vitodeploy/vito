# Headless Console

Here you can run ssh commands on your server and see the result right away.
Note that this is a headless console, it doesn't keep the current path. it will always run from the home path of the selected user.

## Users

You can run commands on your server remotely via Headless Console with these users:

- root
- vito

## How it works?

Headless Console/Terminal will run the commands on your server via SSH connection from the home directory of the chosen user.

After you hit the `Run` button it will open a SSH connection to your server and an open stream to your Vito instance to print out the output of the command in real time.

:::warning
The Console/Terminal is stateless! which means every command will run from the home directory of the chosen user and running something like `cd` and then another command will not go through the new directory.
:::
