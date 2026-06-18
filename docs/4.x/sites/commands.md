# Commands

## Introduction

Commands are saved, named snippets that you can run against a site on demand. They are handy for
repeatable maintenance tasks, such as clearing a cache, running a migration, or rebuilding assets,
without having to open a terminal each time.

Each command runs in the site's root directory, as the site's
[isolated user](/docs/4.x/sites/isolation), so it has exactly the same access and environment as your
site.

## Creating a command

Give the command a **name** and the **command** to run. A command can be a single line or a small
multi-line script. Once saved, it appears in the site's Commands list and can be run at any time.

## Running a command

When you run a command, Vito executes it on the server as a background job and streams the output
back in real time. Each run is recorded, so you can review the output and status of previous
executions.

## Default Commands

Specific site types might come with default commands out of the box. For example, a Laravel site
comes with some useful artisan commands like `artisan up` and `artisan down`.

## Environment

Commands run with the same environment as your [deployment script](/docs/4.x/sites/application), so
the same variables are available, including:

```
SITE_PATH=[path to the website]
DOMAIN=[domain name]
BRANCH=[branch name]
REPOSITORY=[repository]
COMMIT_ID=[last deployed commit id]
PHP_VERSION=[the php version your site is running]
PHP_PATH=[path to the php your site is running]
```

The `php` alias also resolves to the site's configured PHP version, so `php` runs the correct
version automatically.

## Installed tooling

Commands have access to any [Site Tooling](/docs/4.x/sites/site-tooling) you have installed for the
site. Because commands run as the site's isolated user with the tooling on the `PATH`, runtimes and
package managers such as `node`, `npm`, `bun`, `yarn`, and `pnpm` can be used directly in a command,
for example:

```sh
npm ci && npm run build
```

The versions used are whatever you have installed for the site through Site Tooling.

## Variables

Like [Scripts](../scripts.md), commands support variables. Define a variable with the
`${VARIABLE_NAME}` format, and Vito will prompt you for its value each time you run the command. This
lets you reuse a single command with different inputs.
