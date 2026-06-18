# Console / Terminal

## Introduction

The Console is a full, interactive terminal for your server, right in the browser. It is useful for
running commands, installing packages, or inspecting the system without opening your own SSH client.

In v4.x this is a **live terminal**. Vito opens a real interactive SSH shell to the server and streams
it to the browser over a WebSocket, so it behaves like a normal terminal.

:::info
Unlike earlier versions, the terminal is now **stateful and interactive**. Commands like `cd` carry
over to the next command, and interactive programs (such as `top`, `vim`, or `nano`) work as
expected.
:::

## Opening the terminal

Navigate to your server and click the **terminal icon** in the top right corner. The terminal opens
in a new window and connects automatically.

## Choosing a user

A user selector at the top of the terminal lets you choose which system user to connect as. The
available users are:

- `root`
- The server's main Vito user (for example `vito`)
- Any [isolated users](/docs/4.x/sites/isolation) on the server
- Any site users

Switching the user starts a fresh session connected as that user.

:::info
When you connect as an isolated user, any [Site Tooling](/docs/4.x/sites/site-tooling) installed for
that user is available in the shell. Runtimes and package managers such as `node`, `npm`, `bun`,
`yarn`, and `pnpm` can be used directly, just as they can in [commands](/docs/4.x/sites/commands).
:::

## Sessions

The terminal connects as soon as it opens. A status indicator next to the server name shows the
connection state (connecting, connected, or error).

To start over, click the **New Session** button (the refresh icon). This closes the current shell and
opens a new one. The terminal also resizes to fit the window.

:::warning
The terminal runs commands directly on your server as the selected user, with that user's full
permissions. Be careful when connected as `root`.
:::
