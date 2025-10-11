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
        $this->site();
        $this->general();
        $this->database();
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

    private function site(): void
    {
        RegisterWorkflowAction::make('create-site')
            ->label('Create Site')
            ->category('site')
            ->handler(\App\WorkflowActions\Site\CreateSite::class)
            ->register();
        RegisterWorkflowAction::make('deploy-site')
            ->label('Deploy Site')
            ->category('site')
            ->handler(\App\WorkflowActions\Site\DeploySite::class)
            ->register();
    }

    private function general(): void
    {
        RegisterWorkflowAction::make('notify')
            ->label('Notify')
            ->category('general')
            ->handler(\App\WorkflowActions\General\Notify::class)
            ->register();
    }

    private function database(): void
    {
        RegisterWorkflowAction::make('create-database')
            ->label('Create Database')
            ->category('database')
            ->handler(\App\WorkflowActions\Database\CreateDatabase::class)
            ->register();
    }
}
