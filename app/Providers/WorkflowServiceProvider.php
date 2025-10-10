<?php

namespace App\Providers;

use App\Plugins\RegisterWorkflowAction;
use Illuminate\Support\ServiceProvider;

class WorkflowServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->server();
        $this->service();
    }

    private function server(): void
    {
        RegisterWorkflowAction::make('create-server')
            ->label('Create Server')
            ->category('server')
            ->handler(\App\WorkflowActions\Server\CreateServer::class)
            ->register();
    }

    private function service(): void
    {
        RegisterWorkflowAction::make('install-service')
            ->label('Install Service')
            ->category('service')
            ->handler(\App\WorkflowActions\Service\InstallService::class)
            ->register();
    }
}
