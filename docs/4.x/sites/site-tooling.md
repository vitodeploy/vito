# Site Tooling

## Introduction

Site Tooling lets you install developer runtimes and package managers, such as Node.js or Bun, for a
site without baking them into the site type. Instead of a fixed runtime version, you choose which
tools and versions a site uses, and change them whenever you need to.

Tooling is installed for the site's [isolated user](/docs/4.x/sites/isolation) and made available on
that user's `PATH`, so the commands it provides (for example `node`, `npm`, or `bun`) can be used in
your deployment script and when working on the server.

## Available tools

Vito ships with the following tools out of the box:

| Tool        | Versions      | Commands             |
| ----------- | ------------- | -------------------- |
| **Node.js** | 24, 23, 22    | `node`, `npm`, `npx` |
| **Bun**     | 1.2, 1.1, 1.0 | `bun`, `bunx`        |
| **Yarn**    | 4, 3, 1       | `yarn`               |
| **pnpm**    | 10, 9, 8      | `pnpm`, `pnpx`       |

Runtimes are installed with [Mise](https://mise.jdx.dev), which manages versions per isolated user.

## Managing tooling

Open the site's **Tooling** page to see every available tool with its current status. From here you
can:

- **Install** a tool by choosing a version and clicking **Install**.
- **Change version** by selecting a different version and installing again.
- **Uninstall** a tool you no longer need.

Installs and uninstalls run as background jobs on the server. The page updates in real time as each
operation completes, and you will be notified if one fails (check the site logs for details).

## Required tooling

Some site types require a particular tool to function. For example, a Node site requires the Node.js
runtime. Required tools are installed automatically and **cannot be uninstalled** while the site type
depends on them. The Tooling page marks these and explains which site type requires them.

## Shared isolated users

Tooling is tied to the isolated user, not the individual site. Because an isolated user can be
[shared across several sites](/docs/4.x/sites/isolation#sharing-a-user-across-sites), installing,
changing, or removing a tool affects **every site that shares that user**.

:::warning
When a site shares its isolated user with others, the Tooling page lists the sibling sites and warns
you before you make a change. Changing or removing a tool could break those other sites if they rely
on it.
:::
