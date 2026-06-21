# Workers

## Introduction

Vito installs [Supervisor](http://supervisord.org/) on your server and uses it to provide you background worker functionality.

Modern applications like Laravel use workers for long-running jobs.

## Create Workers

You can create workers by entering the fields.

For example, Consider a Laravel application the command might look like this:

```sh
php /home/vito/YOUR-DOMAIN/artisan queue:work --sleep=3
```

You can also specify which user of the OS should run the worker.

Auto Start, Auto Restart and Numprocs are options of Supervisor which you can read more about them at http://supervisord.org

## Stop, Start and Restart

You can do manual actions on the workers such as Starting, Stopping or Restarting them by clicking the corresponding buttons on the workers page.

## Worker Environment Variables

Each worker can have its own environment variables. Open a worker's **Environment** action to add or edit the variables that are passed to the worker process. This is handy for setting things like `APP_ENV` or queue connection details for a specific worker without touching the site's `.env` file.

## Worker Logs

You can view a worker's output directly from the dashboard. Open the worker's **Logs** action to see the latest output captured by Supervisor, which is useful for debugging a crashed or misbehaving worker.

## Restart All & Resync

- **Restart all**: restarts every worker on the server (or every worker of a site) at once.
- **Resync**: refreshes the workers' statuses from Supervisor. Use this if a worker's status in Vito looks out of date compared to the server.

## Worker Status

A worker can be in one of the following statuses: `running`, `stopped`, `starting`, `stopping`, `restarting`, `creating`, `deleting`, or `failed`. The status updates in real time as actions complete.