# Release Notes

## Migrating to FilamentPHP

We've migrated VitoDeploy from [HTMX](https://htmx.org/) to [FilamentPHP](https://filamentphp.com/). This has reduced a
lot of Frontend files and unified the web application's structure. This will also help us to maintain the project easily
and add new features without having to write frontend code.

FilamentPHP uses Livewire under the hood which will enable us to also leverage the power
of [Livewire](https://livewire.laravel.com/) in the future.

## API Support

VitoDeploy now has API support which will allow you to interact with the application programmatically. This will help
you to automate tasks and integrate VitoDeploy with other services.

The API Documentation is accessible in the `/api-docs/index.html` path of your VitoDeploy instance.

## Server Provider Integration

We've updated server provider integrations to fetch the Regions and Plans dynamically during server creation.

This will help you to choose the region and plan easily while creating a new server.

## Tables and Filters

Migrating to FilamentPHP has enabled us to add Search and Filters to all tables inside.

Now you'll be able to easily looks for data in the tables and filter them based on your needs.