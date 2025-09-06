<?php

namespace App\Vito\Plugins\RichardAnderson\LaravelQueueWorkers;

use App\Plugins\AbstractPlugin;
use App\Vito\Plugins\RichardAnderson\LaravelQueueWorkers\SiteFeatures\QueueWorkers;

class Plugin extends AbstractPlugin
{
    protected string $name = 'LaravelQueueWorkers';

    protected string $description = 'Adds a new Site Feature for Laravel Queue Workers.';

    public function register(): void
    {
        app(QueueWorkers::class)->register();
    }

    public function boot(): void {}
}
