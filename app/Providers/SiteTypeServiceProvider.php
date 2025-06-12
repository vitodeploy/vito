<?php

namespace App\Providers;

use App\DTOs\DynamicField;
use App\DTOs\DynamicForm;
use App\Enums\LoadBalancerMethod;
use App\Plugins\RegisterSiteFeature;
use App\Plugins\RegisterSiteFeatureAction;
use App\Plugins\RegisterSiteType;
use App\SiteFeatures\LaravelOctane\Disable;
use App\SiteFeatures\LaravelOctane\Enable;
use App\SiteTypes\Laravel;
use App\SiteTypes\LoadBalancer;
use App\SiteTypes\PHPBlank;
use App\SiteTypes\PHPMyAdmin;
use App\SiteTypes\PHPSite;
use App\SiteTypes\Wordpress;
use Illuminate\Support\ServiceProvider;

class SiteTypeServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->php();
        $this->phpBlank();
        $this->laravel();
        $this->loadBalancer();
        $this->phpMyAdmin();
        $this->wordpress();
    }

    private function php(): void
    {
        RegisterSiteType::make('php')
            ->label('PHP')
            ->handler(PHPSite::class)
            ->form(DynamicForm::make([
                DynamicField::make('php_version')
                    ->component()
                    ->label('PHP Version'),
                DynamicField::make('source_control')
                    ->component()
                    ->label('Source Control'),
                DynamicField::make('web_directory')
                    ->text()
                    ->label('Web Directory')
                    ->placeholder('For / leave empty')
                    ->description('The relative path of your website from /home/vito/your-domain/'),
                DynamicField::make('repository')
                    ->text()
                    ->label('Repository')
                    ->placeholder('organization/repository'),
                DynamicField::make('branch')
                    ->text()
                    ->label('Branch')
                    ->default('main'),
                DynamicField::make('composer')
                    ->checkbox()
                    ->label('Run `composer install --no-dev`')
                    ->default(false),
            ]))
            ->register();
    }

    private function phpBlank(): void
    {
        RegisterSiteType::make('php-blank')
            ->label('PHP Blank')
            ->handler(PHPBlank::class)
            ->form(DynamicForm::make([
                DynamicField::make('php_version')
                    ->component()
                    ->label('PHP Version'),
                DynamicField::make('web_directory')
                    ->text()
                    ->label('Web Directory')
                    ->placeholder('For / leave empty')
                    ->description('The relative path of your website from /home/vito/your-domain/'),
            ]))
            ->register();
    }

    private function laravel(): void
    {
        RegisterSiteType::make('laravel')
            ->label('Laravel')
            ->handler(Laravel::class)
            ->form(DynamicForm::make([
                DynamicField::make('php_version')
                    ->component()
                    ->label('PHP Version'),
                DynamicField::make('source_control')
                    ->component()
                    ->label('Source Control'),
                DynamicField::make('web_directory')
                    ->text()
                    ->label('Web Directory')
                    ->placeholder('For / leave empty')
                    ->description('The relative path of your website from /home/vito/your-domain/'),
                DynamicField::make('repository')
                    ->text()
                    ->label('Repository')
                    ->placeholder('organization/repository'),
                DynamicField::make('branch')
                    ->text()
                    ->label('Branch')
                    ->default('main'),
                DynamicField::make('composer')
                    ->checkbox()
                    ->label('Run `composer install --no-dev`')
                    ->default(false),
            ]))
            ->register();
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

    public function loadBalancer(): void
    {
        RegisterSiteType::make('load-balancer')
            ->label('Load Balancer')
            ->handler(LoadBalancer::class)
            ->form(DynamicForm::make([
                DynamicField::make('method')
                    ->select()
                    ->label('Load Balancing Method')
                    ->options([
                        LoadBalancerMethod::IP_HASH,
                        LoadBalancerMethod::ROUND_ROBIN,
                        LoadBalancerMethod::LEAST_CONNECTIONS,
                    ]),
            ]))
            ->register();
    }

    public function phpMyAdmin(): void
    {
        RegisterSiteType::make('phpmyadmin')
            ->label('PHPMyAdmin')
            ->handler(PHPMyAdmin::class)
            ->form(DynamicForm::make([
                DynamicField::make('php_version')
                    ->component()
                    ->label('PHP Version'),
            ]))
            ->register();
    }

    public function wordpress(): void
    {
        RegisterSiteType::make('wordpress')
            ->label('WordPress')
            ->handler(Wordpress::class)
            ->form(DynamicForm::make([
                DynamicField::make('php_version')
                    ->component()
                    ->label('PHP Version'),
                DynamicField::make('title')
                    ->text()
                    ->label('Site Title')
                    ->placeholder('My WordPress Site'),
                DynamicField::make('username')
                    ->text()
                    ->label('Admin Username')
                    ->placeholder('admin'),
                DynamicField::make('password')
                    ->text()
                    ->label('Admin Password'),
                DynamicField::make('email')
                    ->text()
                    ->label('Admin Email'),
                DynamicField::make('database')
                    ->text()
                    ->label('Database Name')
                    ->placeholder('wordpress'),
                DynamicField::make('database_user')
                    ->text()
                    ->label('Database User')
                    ->placeholder('wp_user'),
                DynamicField::make('database_password')
                    ->text()
                    ->label('Database Password'),
            ]))
            ->register();
    }
}
