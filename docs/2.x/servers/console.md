# Headless Console

## Introduction

Sometimes you need to run commands on your server to do some tasks like installing packages, updating the system, etc
and you don't want to SSH to the server due to some reasons like security, etc.

Headless Console is a feature that allows you to run commands on your server remotely via SSH connection.

## Supported Users

You can run commands on your server remotely via Headless Console with these users:

- root
- vito

## Run commands

Headless Console/Terminal will run the commands on your server via SSH connection from the home directory of the chosen
user.

After you hit the `Run` button it will open a SSH connection to your server and an open stream to your Vito instance to
print out the output of the command in real time.

:::warning
The Console/Terminal is stateless! which means every command will run from the home directory of the chosen user and
running something like `cd` and then another command will not go through the new directory.
:::
