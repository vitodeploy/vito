<?php

namespace App\Providers;

use App\Plugins\RegisterWorkflowAction;
use App\WorkflowActions\Database\CreateDatabase;
use App\WorkflowActions\Database\CreateDatabaseUser;
use App\WorkflowActions\Domain\CreateDNSRecord;
use App\WorkflowActions\Domain\DeleteDNSRecord;
use App\WorkflowActions\General\HttpCall;
use App\WorkflowActions\General\Notify;
use App\WorkflowActions\General\RunCommand;
use App\WorkflowActions\Server\CreateServer;
use App\WorkflowActions\Service\InstallService;
use App\WorkflowActions\Site\CreateLaravelSite;
use App\WorkflowActions\Site\CreateLoadBalancerSite;
use App\WorkflowActions\Site\CreateNodeJsSite;
use App\WorkflowActions\Site\CreatePHPBlankSite;
use App\WorkflowActions\Site\CreatePHPMyAdminSite;
use App\WorkflowActions\Site\CreatePHPSite;
use App\WorkflowActions\Site\CreateWordpressSite;
use App\WorkflowActions\Site\DeploySite;
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
        $this->domain();
    }

    private function server(): void
    {
        RegisterWorkflowAction::make('create-server')
            ->label('Create Server')
            ->category('server')
            ->handler(CreateServer::class)
            ->register();
    }

    private function service(): void
    {
        RegisterWorkflowAction::make('install-service')
            ->label('Install Service')
            ->category('service')
            ->handler(InstallService::class)
            ->register();
    }

    private function site(): void
    {
        RegisterWorkflowAction::make('create-php-site')
            ->label('Create PHP Site')
            ->category('site')
            ->handler(CreatePHPSite::class)
            ->register();
        RegisterWorkflowAction::make('create-php-blank-site')
            ->label('Create PHP Blank Site')
            ->category('site')
            ->handler(CreatePHPBlankSite::class)
            ->register();
        RegisterWorkflowAction::make('create-wordpress-site')
            ->label('Create WordPress Site')
            ->category('site')
            ->handler(CreateWordpressSite::class)
            ->register();
        RegisterWorkflowAction::make('create-phpmyadmin-site')
            ->label('Create PHPMyAdmin Site')
            ->category('site')
            ->handler(CreatePHPMyAdminSite::class)
            ->register();
        RegisterWorkflowAction::make('create-laravel-site')
            ->label('Create Laravel Site')
            ->category('site')
            ->handler(CreateLaravelSite::class)
            ->register();
        RegisterWorkflowAction::make('create-nodejs-site')
            ->label('Create NodeJS Site')
            ->category('site')
            ->handler(CreateNodeJsSite::class)
            ->register();
        RegisterWorkflowAction::make('create-load-balancer-site')
            ->label('Create Load Balancer Site')
            ->category('site')
            ->handler(CreateLoadBalancerSite::class)
            ->register();
        RegisterWorkflowAction::make('create-nodejs-site')
            ->label('Create NodeJS Site')
            ->category('site')
            ->handler(CreateNodeJsSite::class)
            ->register();
        RegisterWorkflowAction::make('deploy-site')
            ->label('Deploy Site')
            ->category('site')
            ->handler(DeploySite::class)
            ->register();
    }

    private function general(): void
    {
        RegisterWorkflowAction::make('notify')
            ->label('Notify')
            ->category('general')
            ->handler(Notify::class)
            ->register();
        RegisterWorkflowAction::make('run-command')
            ->label('Run Command')
            ->category('general')
            ->handler(RunCommand::class)
            ->register();
        RegisterWorkflowAction::make('http-call')
            ->label('HTTP Call')
            ->category('general')
            ->handler(HttpCall::class)
            ->register();
    }

    private function database(): void
    {
        RegisterWorkflowAction::make('create-database')
            ->label('Create Database')
            ->category('database')
            ->handler(CreateDatabase::class)
            ->register();
        RegisterWorkflowAction::make('create-database-user')
            ->label('Create Database User')
            ->category('database')
            ->handler(CreateDatabaseUser::class)
            ->register();
    }

    private function domain(): void
    {
        RegisterWorkflowAction::make('create-dns-record')
            ->label('Create DNS Record')
            ->category('domain')
            ->handler(CreateDNSRecord::class)
            ->register();
        RegisterWorkflowAction::make('delete-dns-record')
            ->label('Delete DNS Record')
            ->category('domain')
            ->handler(DeleteDNSRecord::class)
            ->register();
    }
}
