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