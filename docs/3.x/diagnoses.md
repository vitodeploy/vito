# Diagnoses and Troubleshooting

## Introduction

Vito is a Laravel application, and it ships with diagnostic tools to help you troubleshoot issues with your Vito
instance.

In case of issues, you can open a new issue on the [Vito GitHub repository](https://github.com/vitodeploy/vito) or ask
for help in the [VitoDeploy Discord Server](https://discord.com/invite/uZeeHZZnm5).

## Logs

You can view the logs of your Vito instance in the `/logs`. This section shows you the logs of your Vito instance,
including the logs of the web server, PHP, and other services.

:::info
Logs are visible on to Admin users only.
:::

## Horizon

Vito uses Laravel Horizon to manage the background jobs. You can view the status of the jobs in the `/horizon`

section. This section shows you the status of the jobs, including the failed jobs and the running jobs.

:::info
Horizon is visible on to Admin users only.
:::