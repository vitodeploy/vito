# Queues

Vito installs [Supervisor](http://supervisord.org/) on your server and uses it to provide you Queue functionality.

Modern applications like Laravel use queues for long running jobs.

Here you can create queues and manage them.

## Create

You can create queues by entering the fields.

For example, Consider a Laravel application the command might look like this:

```sh
php /home/vito/YOUR-DOMAIN/artisan queue:work --sleep=3
```

You can also specify which user of the OS should run the queue.

Auto Start, Auto Restart and Numprocs are options of Supervisor which you can read more about them in their [website](http://supervisord.org)

## Stop, Start and Restart

You can do manual actions on the queues such as Starting, Stopping or Restarting them by clicking the corresponding buttons on the queues page.