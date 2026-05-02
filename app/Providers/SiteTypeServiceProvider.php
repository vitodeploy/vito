<?php

namespace App\Providers;

use App\DTOs\DynamicField;
use App\DTOs\DynamicForm;
use App\Enums\LoadBalancerMethod;
use App\Plugins\RegisterSiteFeature;
use App\Plugins\RegisterSiteFeatureAction;
use App\Plugins\RegisterSiteType;
use App\SiteFeatures\ModernDeployment\Configuration;
use App\SiteFeatures\ModernDeployment\Disable;
use App\SiteFeatures\ModernDeployment\Enable;
use App\SiteTypes\Laravel;
use App\SiteTypes\LoadBalancer;
use App\SiteTypes\NodeJS;
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
        $this->nodeJS();
        $this->loadBalancer();
        $this->phpMyAdmin();
        $this->wordpress();
    }

    private function php(): void
    {
        RegisterSiteType::make(PHPSite::id())
            ->label('PHP')
            ->handler(PHPSite::class)
            ->form(DynamicForm::make([
                DynamicField::make('php_version')
                    ->component()
                    ->label('PHP Version'),
                DynamicField::make('source_control')
                    ->component()
                    ->label('Source Control'),
                DynamicField::make('repository')
                    ->text()
                    ->component()
                    ->label('Repository'),
                DynamicField::make('branch')
                    ->component()
                    ->label('Branch'),
                DynamicField::make('web_directory')
                    ->text()
                    ->label('Web Directory')
                    ->placeholder('e.g., public, www, dist (leave empty for root)')
                    ->description('The relative path of your website from /home/vito/your-domain/'),
                DynamicField::make('composer')
                    ->checkbox()
                    ->label('Run `composer install --no-dev`')
                    ->default(false),
            ]))
            ->register();
    }

    private function phpBlank(): void
    {
        RegisterSiteType::make(PHPBlank::id())
            ->label('PHP Blank')
            ->handler(PHPBlank::class)
            ->form(DynamicForm::make([
                DynamicField::make('php_version')
                    ->component()
                    ->label('PHP Version'),
                DynamicField::make('web_directory')
                    ->text()
                    ->label('Web Directory')
                    ->placeholder('e.g., public, www, dist (leave empty for root)')
                    ->description('The relative path of your website from /home/vito/your-domain/'),
            ]))
            ->register();
    }

    private function laravel(): void
    {
        RegisterSiteType::make(Laravel::id())
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
                    ->default('public')
                    ->placeholder('e.g., public, www, dist (leave empty for root)')
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
        RegisterSiteFeature::make(Laravel::id(), 'modern-deployment')
            ->label('Modern Deployment (beta)')
            ->description('Enables zero downtime deployment and deployment rollbacks')
            ->register();
        RegisterSiteFeatureAction::make(Laravel::id(), 'modern-deployment', 'enable')
            ->label('Enable')
            ->handler(Enable::class)
            ->register();
        RegisterSiteFeatureAction::make(Laravel::id(), 'modern-deployment', 'disable')
            ->label('Disable')
            ->handler(Disable::class)
            ->register();
        RegisterSiteFeatureAction::make(Laravel::id(), 'modern-deployment', 'configuration')
            ->label('Configure')
            ->handler(Configuration::class)
            ->register();
    }

    private function nodeJS(): void
    {
        RegisterSiteType::make(NodeJS::id())
            ->label('NodeJS with NPM')
            ->handler(NodeJS::class)
            ->form(DynamicForm::make([
                DynamicField::make('source_control')
                    ->component()
                    ->label('Source Control'),
                DynamicField::make('port')
                    ->text()
                    ->label('Port')
                    ->placeholder('3000')
                    ->description('On which port your app will be running'),
                DynamicField::make('repository')
                    ->text()
                    ->label('Repository')
                    ->placeholder('organization/repository')
                    ->description('Your package.json must have start and build scripts'),
                DynamicField::make('branch')
                    ->text()
                    ->label('Branch')
                    ->default('main'),
            ]))
            ->register();
    }

    public function loadBalancer(): void
    {
        RegisterSiteType::make(LoadBalancer::id())
            ->label('Load Balancer')
            ->handler(LoadBalancer::class)
            ->form(DynamicForm::make([
                DynamicField::make('method')
                    ->select()
                    ->label('Load Balancing Method')
                    ->options([
                        LoadBalancerMethod::IP_HASH->value,
                        LoadBalancerMethod::ROUND_ROBIN->value,
                        LoadBalancerMethod::LEAST_CONNECTIONS->value,
                    ]),
            ]))
            ->register();
    }

    public function phpMyAdmin(): void
    {
        RegisterSiteType::make(PHPMyAdmin::id())
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
        RegisterSiteType::make(Wordpress::id())
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
                    ->placeholder('wordpress')
                    ->componentProps(['defaultCharset' => 'utf8mb4', 'defaultCollation' => 'utf8mb4_0900_ai_ci']),
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
