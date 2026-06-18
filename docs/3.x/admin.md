# Admin

## Introduction

You can manage some of the features of your Vito instance from its admin area and some others from the server terminal.

## Admin Area

The following features can be managed in the admin area:

### Users

Vito allows you to manage users from the admin area. You can create, edit, delete users and also assign roles to them.

### Export and Import

You can export and import everything that Vito has stored including servers and logs using the Export and Import feature in the admin area. This is useful when you want to migrate to another Vito instance or create a backup of your current Vito instance.

### Plugins

You can manage Vito plugins from the admin area. You can install, activate, deactivate and delete plugins.

Read more about plugins in the [Plugins Documentation](./plugins.md).

## Terminal

Vito ships some cli commands that you can use to manage your Vito instance from the terminal. You can see the list of available commands by running:

### Clear Logs

Running the following command will clear/delete all server logs from the server.

```bash
php artisan logs:clear
```

### Create User

This command will create a new user for your Vito instance.

```bash
php artisan user:create [name] [email] [password] [--role=admin]
```

### Delete older metrics

This command will delete older monitoring metrics from the database.

```bash
php artisan metrics:delete-older-metrics
```

### Generate SSH Keys

Vito has default set of a public and private SSH keys when you install it. The keys are basically being used to connect to the Custom server types to authenticate and then they are being deleted from the target server. You have the option to re-generate these keys.

```bash
php artisan ssh-key:generate {--force}
```

The keys are located at `storage` directory.

:::warning
Make sure you always have a backup of existing keys before running this command.
:::

