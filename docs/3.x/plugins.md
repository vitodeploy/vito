# Plugins

## Introduction

Vito supports plugins to extend its functionality and integrate with various services.

Plugins can be used to add new features or change the behavior of existing ones.

## What can Plugins Do?

Plugins can change or extend the following features:

- **Server Providers**: Add support for new server providers or modify existing ones.
- **Storage Providers**: Integrate with new storage providers or modify existing storage functionalities.
- **Source Controls**: Integrate with new source control systems or modify existing source control functionalities.
- **Notification Channels**: Integrate with new notification channels or modify existing ones.
- **Services**: Add new services or modify existing ones.
- **Site Types**: Add new site types or modify existing ones.
- **Site Features**: Add new site features or modify existing ones.

## Installing and Managing Plugins

Vito offers official and community plugins. Official plugins are developed and maintained by the Vito team, while
community plugins are developed by the community.

### Install

You can install and manage plugins through the Vito web interface. Just navigate to the **Admin > Plugins** section, and find `Official` and `Community` tabs to browse and install plugins.

### Enable

After installing a plugin, In order to use the plugin, you need to enable it. You can find the installed plugins in the `Installed` tab in the `Admin > Plugins` section. Click on the three dots on the right side of the plugin and then click on `Enable`.

### Disable

Disabling a plugin will stop it from being booted by Vito. However the plugin's code will still live in your Vito instance and you can enable it again.

### Uninstall

If you don't need a plugin anymore, you can uninstall it. Uninstalling a plugin will remove its code from your Vito instance.

## Plugin Development

### Creating a Plugin

Plugins are basically code extensions that are installed via Vito's plugin management system.

Plugins will live inside `app/Vito/Plugins` directory and will be a part of the Vito application.

You can use Vito's [plugin template](https://github.com/vitodeploy/plugin-template) to create a new plugin.

:::info
Take a look at one example plugin to see how it works: [VitoDeploy Laravel Octane Plugin](https://github.com/vitodeploy/laravel-octane-plugin)
:::

### Local Setup

In order to develop plugins locally, you can put them directly into the `app/Vito/Plugins/YourGithubUsername/YourRepoName` and Vito will discover your plugin.

:::warning
If your repo for the plugin is `my-username/my-vito-plugin`, the directory must be `app/Vito/Plugins/MyUsername/MyVitoPlugin` and the namespace of your plugin must be `App\Vito\Plugins\MyUsername\MyVitoPlugin`.
:::

### Plugin.php

Every plugin must have a `Plugin.php` file in its root directory which extends the `App\Plugins\AbstractPlugin` class.

The `Plugin.php` must implement the `boot` method where you can register your plugin's features.

Example:

```php
<?php

namespace App\Vito\Plugins\RichardAnderson\LaravelOctanePlugin;

use App\Plugins\AbstractPlugin;
use App\Plugins\RegisterSiteFeature;
use App\Plugins\RegisterSiteFeatureAction;
use App\Vito\Plugins\RichardAnderson\LaravelOctanePlugin\Actions\Disable;
use App\Vito\Plugins\RichardAnderson\LaravelOctanePlugin\Actions\Enable;

class Plugin extends AbstractPlugin
{
    protected string $name = 'Laravel Octane Plugin';

    protected string $description = 'Laravel Octane plugin for VitoDeploy';

    public function boot(): void
    {
        RegisterSiteFeature::make('laravel', 'laravel-octane')
            ->label('Laravel Octane')
            ->description('Enable Laravel Octane for this site')
            ->register();
        RegisterSiteFeatureAction::make('laravel', 'laravel-octane', 'enable')
            ->label('Enable')
            ->handler(Enable::class)
            ->register();
        RegisterSiteFeatureAction::make('laravel', 'laravel-octane', 'disable')
            ->label('Disable')
            ->handler(Disable::class)
            ->register();
    }
}
```

### Discover Plugins

After you create your plugin, you will need to navigate to the `Admin > Plugins` section in the Vito web interface and then `Discover` tab.

Your new plugin should be listed there and you can install it from there.

### Error Handling

Vito records every error that happens in the plugins when they're being booted and in case of an error, it will disable the plugin and show the error message in the `Admin > Plugins` section.

You can also see the stack trace of the error by viewing the error logs of a plugin to debug it.

### Register site types

By registering a site type, you can add a new type of site that can be created in Vito.

Use `App\Plugins\RegisterSiteType` to register a new site type using the `boot` method of your `Plugin.php` file.

```php
\App\Plugins\RegisterSiteType::make('symfony')
    ->label('Symfony')
    ->handler(SymfonyHandler::class)
    ->form(\App\DTOs\DynamicForm::make([
        \App\DTOs\DynamicField::make('php_version')
            ->component()
            ->label('PHP Version'),
        \App\DTOs\DynamicField::make('web_directory')
            ->text()
            ->label('Web Directory')
            ->placeholder('For / leave empty')
            ->description('The relative path of your website from /home/vito/your-domain/'),
    ]))
    ->register();
```

The `handler` method should point to a class that extends `App\SiteTypes\AbstractSiteType` or implements
`App\SiteTypes\SiteType`.

:::info
You can find some of the built-in site types are
in [Site Types](https://github.com/vitodeploy/vito/tree/3.x/app/SiteTypes)
:::

### Register site features and actions

Every site can have multiple features and every feature can have multiple actions.

For example, Laravel website has a feature called `Laravel Octane` which has actions like `Enable` and `Disable`.

You can register a new site feature for an already registered site type by using `App\Plugins\RegisterSiteFeature` in the `boot` method of your `Plugin.php` file.

Vito allows you to register a feature to a site with actions or register actions to an already existing feature.

```php
// register feature
\App\Plugins\RegisterSiteFeature::make('laravel', 'laravel-octane')
    ->label('Laravel Octane')
    ->description('Enable Laravel Octane for this site')
    ->register();
// register actions for the feature
\App\Plugins\RegisterSiteFeatureAction::make('laravel', 'laravel-octane', 'enable')
    ->label('Enable')
    ->form(\App\DTOs\DynamicForm::make([
        \App\DTOs\DynamicField::make('alert')
            ->alert()
            ->label('Alert')
            ->description('Make sure you have already set the `OCTANE_SERVER` in your `.env` file'),
        \App\DTOs\DynamicField::make('port')
            ->text()
            ->label('Octane Port')
            ->default(8000)
            ->description('The port on which Laravel Octane will run.'),
    ]))
    ->handler(Enable::class)
    ->register();
\App\Plugins\RegisterSiteFeatureAction::make('laravel', 'laravel-octane', 'disable')
    ->label('Disable')
    ->handler(Disable::class)
    ->register();
```

:::info
You can define forms when you're registering a site feature or inside the site feature action. It is recommended to use the action form as you will have access to the site object.
:::

Every feature must implement the `App\SiteFeatures\FeatureInterface` interface.

Every action must extend the `App\SiteFeatures\Action` class or implement the `App\SiteFeatures\ActionInterface`
interface.

:::info
You can find an example of a site feature in
the [Laravel Octane Plugin](https://github.com/vitodeploy/laravel-octane-plugin)
:::

### Register server features and actions

Servers can also have features and feature actions.

You can register a new server feature using `App\Plugins\RegisterServerFeature` in the `boot` method of your `Plugin.php` file.

Vito allows you to register a feature to a server with actions or register actions to an already existing feature.

```php
// register feature
\App\Plugins\RegisterServerFeature::make('opcache')
    ->label('OPCache')
    ->description('Enable OPCache for PHP')
    ->register();
// register actions for the feature
\App\Plugins\RegisterServerFeatureAction::make('opcache', 'enable')
    ->label('Enable')
    ->form(\App\DTOs\DynamicForm::make([
      ...
    ]))
    ->handler(Enable::class)
    ->register();
\App\Plugins\RegisterServerFeatureAction::make('opcache', 'disable')
    ->label('Disable')
    ->handler(Disable::class)
    ->register();
```

Every feature must implement the `App\ServerFeatures\FeatureInterface` interface.

Every action must extend the `App\ServerFeatures\Action` class or implement the `App\ServerFeatures\ActionInterface`
interface.

### Register services

Vito is a service-oriented server management system. By default, it comes with some built-in services like Nginx, MySQL,
Redis, etc.

However, you can register your own services using `App\Plugins\RegisterService` in the `boot` method of your `Plugin.php` file.

```php
RegisterServiceType::make(Nginx::id())
    ->type(Nginx::type())
    ->label('Nginx')
    ->handler(Nginx::class)
    ->configPaths([
        [
            'name' => 'nginx.conf',
            'path' => '/etc/nginx/nginx.conf',
            'sudo' => true,
        ],
    ])
    ->register();
\App\Plugins\RegisterServiceType::make('php')
    ->type('php')
    ->label('PHP')
    ->handler(PHP::class)
    ->versions([
        '8.4',
        '8.3',
        '8.2',
        '8.1',
        '8.0',
        '7.4',
        '7.3',
        '7.2',
        '7.1',
        '7.0',
        '5.6',
    ])
    ->data([
        'extensions' => [
            'imagick',
            'exif',
            'gmagick',
            'gmp',
            'intl',
            'sqlite3',
            'opcache',
        ],
    ])
    ->register();
```

**Service Types:**

Vito already supports multiple service types, and you can create alternatives for them.

For example, You can create an alternative web server service like Apache. or another database service like SQL server.

Supported service types are:

- `webserver`: Will be used for sites
- `database`: Will be used for databases
- `monitoring`: Will be used to monitor the server
- `memory_database`: Will be used for in-memory databases like Redis or Memcached
- `php`: Will be used for PHP versions
- `nodejs`: Will be used for Node.js versions
- `process_manager`: Will be used for Workers like Supervisor
- `firewall`: Will be used for firewall services like UFW or CSF

:::info
These were the services Vito has built-in web interface for them. You are not limited to these service types, and you
can create your own.
:::

**Service Handlers:**

Depending on your service type, You will need to implement a handler for your service which implements the interface of
that service type.

For example, If you develop a web server service, you will need to implement the `App\Services\Webserver\Webserver`
interface or extend the `App\Services\Webserver\AbstractWebserver` class.

**Service Config Files**

You can register your service's config files using the `configPaths` method. Vito will allow you to modify these files in the Services page.

:::info
For a non-listed service types, you can implement the `App\Services\ServiceInterface` interface or extend the
`App\Services\AbstractService` class.
:::

:::info
You can find plenty of examples in the [Services](https://github.com/vitodeploy/vito/tree/3.x/app/Services)
:::

### Register server providers

Vito already covers the most popular server providers like DigitalOcean, AWS, Vultr, Hetzner etc.

You can register your own server provider using the `boot` method in your `Plugin.php` file.

```php
\App\Plugins\RegisterServerProvider::make('hetzner')
    ->label('Hetzner')
    ->handler(Hetzner::class)
    ->form(
        \App\DTOs\DynamicForm::make([
            \App\DTOs\DynamicField::make('token')
                ->text()
                ->label('API Token'),
        ])
    )
    ->defaultUser('root') // The default ssh user of the server that the provider will create
    ->register();
```

The handler must implement the `App\ServerProviders\ServerProvider` interface or extend the
`App\ServerProviders\AbstractServerProvider` class.

:::info
You can find plenty of examples in
the [Server Providers](https://github.com/vitodeploy/vito/tree/3.x/app/ServerProviders)
:::

### Register storage providers

Vito supports multiple storage providers like S3 compatible, FTP, etc.

You can register your own storage provider using `App\Plugins\RegisterStorageProvider` in the `boot` method of your `Plugin.php` file.

```php
\App\Plugins\RegisterStorageProvider::make('local')
    ->label('Local')
    ->handler(Local::class)
    ->form(
        \App\DTOs\DynamicForm::make([
            \App\DTOs\DynamicField::make('path')
                ->text()
                ->label('Path'),
        ])
    )
    ->register();
```

The handler must implement the `App\StorageProviders\StorageProvider` interface or extend the
`App\StorageProviders\AbstractStorageProvider` class.

:::info
You can find plenty of examples in
the [Storage Providers](https://github.com/vitodeploy/vito/tree/3.x/app/StorageProviders)
:::

### Register source controls

Vito supports multiple source control providers like GitHub, GitLab, etc.

You can register your own source control provider using `App\Plugins\RegisterSourceControl` in the `boot` method of your `Plugin.php` file.

```php
\App\Plugins\RegisterSourceControl::make('github')
    ->label('GitHub')
    ->handler(GitHub::class)
    ->form(
        \App\DTOs\DynamicForm::make([
            \App\DTOs\DynamicField::make('token')
                ->text()
                ->label('Personal Access Token'),
        ])
    )
    ->register();
```

The handler must implement the `App\SourceControls\SourceControl` interface or extend the
`App\SourceControls\AbstractSourceControl` class.

:::info
You can find plenty of examples in the [Source Controls](https://github.com/vitodeploy/vito/tree/3.x/app/SourceControls)
:::

### Register notification channels

Vito supports multiple notification channels like Email, Slack, etc.

You can register your own notification channel using `App\Plugins\RegisterNotificationChannel` in the `boot` method of your `Plugin.php` file.

```php
\App\Plugins\RegisterNotificationChannel::make('slack')
    ->label('Slack')
    ->handler(Slack::class)
    ->form(
        \App\DTOs\DynamicForm::make([
            \App\DTOs\DynamicField::make('webhook_url')
                ->text()
                ->label('Webhook URL'),
        ])
    )
    ->register();
```

The handler must implement the `App\NotificationChannels\NotificationChannel` interface or extend the
`App\NotificationChannels\AbstractNotificationChannel` class.

:::info
You can find plenty of examples in
the [Notification Channels](https://github.com/vitodeploy/vito/tree/3.x/app/NotificationChannels)
:::

### Register Workflow Actions

[Workflows](./workflows.md) can be extended by adding new actions. You can register your own workflow action using `App\Plugins\RegisterWorkflowAction` in the `boot` method of your `Plugin.php` file.

You can find handful examples of workflow actions in the [Workflow Actions](https://github.com/vitodeploy/vito/tree/3.x/app/WorkflowActions)

### Events

Vito fires events during `Service` installation and uninstallation this way you can tweak/extend the installation process, for example by modifying configuration files or setting up database tables/records.

You can listen to these events in the `boot` method of your `Plugin.php` file:

```php
use App\Events\ServiceInstalled;
use App\Events\ServiceUninstalled;

class Plugin extends AbstractPlugin
{
    public function boot()
    {
        Event::listen('service.installed', function (Service $service) {
            // Do something when the service is installed
            Log::info("Service installed: {$service->id}");
        });
        Event::listen('service.uninstalled', function (Service $service) {
            // Do something when the service is uninstalled
            Log::info("Service uninstalled: {$service->id}");
        });
    }
}
```

### Store Service Data

You can leverage the Service's `type_data` property to store any additional data you need, don't forget to honor other's properties when mutating it.

### Dynamic Fields

Dynamic fields are a set of input fields designed to collect inputs from the action forms. They can be used in site creation, site feature actions, and every other part that Vito enables you to extend.

Here are the list of supported fields:

- Label
- Text field
- Checkbox
- Select field
- Alert (with types: info, warning, error, success)
- Link

For more details, check `App\DTOs\DynamicField::class`

### Hooks

Vito exposes hooks for `install`, `uninstall`, `enable` and `disable` of plugins. This means, if you implement these methods in your `Plugin.php`, they will be called when the respective action is performed.

Example:

```php
<?php

class Plugin extends AbstractPlugin
{
    protected string $name = 'Name';

    protected string $description = 'Description';

    public function boot(): void
    {
        //
    }

    public function enable(): void
    {
        //
    }

    public function disable(): void
    {
        //
    }

    public function install(): void
    {
        //
    }

    public function uninstall(): void
    {
        //
    }
}
```

### Register Commands

If your plugin wants to register any console commands, you need to register them via the RegisterCommand helper, as per below;

```php
RegisterCommand::make(FetchCommand::class)->register();
```

### Register Views

If your plugin needs to register any views to use within it's implementations, you will need to register these views with a unique name via the RegisterViews helper;

```php
RegisterViews::make('vitodeploy-reverb')
            ->path(__DIR__.'/views')
            ->register();
```

## Publishing a Plugin

Vito has a community plugins section on the web interface that users can install plugins from. To list your plugin
there, You need to publish your plugin as a public repository on GitHub and then add `vitodeploy-plugin` topic to it.

## Versioning

Plugins are using Github tags and releases for versioning.

## Updating Plugins

In order to check for available updates for your installed plugins, navigate to the `Admin > Plugins` section in the Vito web interface and then click on the `Check for updates` button. If there was an available update, you will be able to click on Update on every plugin's dropdown to update it.
