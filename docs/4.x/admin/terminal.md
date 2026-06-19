# Terminal

## Introduction

Some of your Vito instance can be managed from the command line. These commands are run on the Vito
host itself, from the project root (for example `/home/vito/vito`). For the Docker version, run them
inside the container.

## Clear Logs

Delete all server logs from the database.

```bash
php artisan logs:clear
```

## Create User

Create a new user for your Vito instance. The role defaults to `admin`.

```bash
php artisan user:create [name] [email] [password] [--role=admin]
```

## Delete older metrics

Delete older monitoring metrics from the database to keep it lean.

```bash
php artisan metrics:delete-older-metrics
```

## Generate SSH Keys

Vito has a default public/private SSH key pair created on installation. These keys are used to
connect to Custom servers to authenticate, and are then removed from the target server. You can
re-generate them if needed.

```bash
php artisan ssh-key:generate {--force}
```

The keys are located in the `storage` directory.

:::warning
Always keep a backup of your existing keys before running this command. Servers connected with the
old keys will no longer be reachable once the keys change.
:::

## Maintenance commands

Vito runs a number of background tasks on a schedule (for example checking server connections,
collecting metrics, and renewing certificates). You normally never need to run these yourself, but it
can be useful to trigger one manually, for example to force an immediate check rather than waiting for
the next scheduled run.

### Check server connections

Re-check the SSH connection status of all servers.

```bash
php artisan servers:check
```

### Check for server updates

Check each server for available OS package updates.

```bash
php artisan servers:check-updates
```

### Check pending domains

Check DNS resolution for hosted domains that are still pending, activating any that now resolve.

```bash
php artisan domains:check-pending
```

### Sync GitHub App installations

Reconcile your local GitHub App source controls with the installations on GitHub. This is the same
action as the **Sync** button on the [GitHub App](/docs/4.x/admin/github-app) page.

```bash
php artisan github-app:sync
```