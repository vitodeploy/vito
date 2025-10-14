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
        RegisterWorkflowAction::make('create-php-site')
            ->label('Create PHP Site')
            ->category('site')
            ->handler(\App\WorkflowActions\Site\CreatePHPSite::class)
            ->register();
        RegisterWorkflowAction::make('create-php-blank-site')
            ->label('Create PHP Blank Site')
            ->category('site')
            ->handler(\App\WorkflowActions\Site\CreatePHPBlankSite::class)
            ->register();
        RegisterWorkflowAction::make('create-wordpress-site')
            ->label('Create WordPress Site')
            ->category('site')
            ->handler(\App\WorkflowActions\Site\CreateWordpressSite::class)
            ->register();
        RegisterWorkflowAction::make('create-phpmyadmin-site')
            ->label('Create PHPMyAdmin Site')
            ->category('site')
            ->handler(\App\WorkflowActions\Site\CreatePHPMyAdminSite::class)
            ->register();
        RegisterWorkflowAction::make('create-laravel-site')
            ->label('Create Laravel Site')
            ->category('site')
            ->handler(\App\WorkflowActions\Site\CreateLaravelSite::class)
            ->register();
        RegisterWorkflowAction::make('create-nodejs-site')
            ->label('Create NodeJS Site')
            ->category('site')
            ->handler(\App\WorkflowActions\Site\CreateNodeJsSite::class)
            ->register();
        RegisterWorkflowAction::make('create-load-balancer-site')
            ->label('Create Load Balancer Site')
            ->category('site')
            ->handler(\App\WorkflowActions\Site\CreateLoadBalancerSite::class)
            ->register();
        RegisterWorkflowAction::make('create-nodejs-site')
            ->label('Create NodeJS Site')
            ->category('site')
            ->handler(\App\WorkflowActions\Site\CreateNodeJsSite::class)
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
        RegisterWorkflowAction::make('run-command')
            ->label('Run Command')
            ->category('general')
            ->handler(\App\WorkflowActions\General\RunCommand::class)
            ->register();
    }

    private function database(): void
    {
        RegisterWorkflowAction::make('create-database')
            ->label('Create Database')
            ->category('database')
            ->handler(\App\WorkflowActions\Database\CreateDatabase::class)
            ->register();
        RegisterWorkflowAction::make('create-database-user')
            ->label('Create Database User')
            ->category('database')
            ->handler(\App\WorkflowActions\Database\CreateDatabaseUser::class)
            ->register();
    }
}
