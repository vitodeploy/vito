<?php

namespace App\Providers;

use App\DTOs\DynamicField;
use App\DTOs\DynamicForm;
use App\Plugins\RegisterServerProvider;
use App\ServerProviders\AWS;
use App\ServerProviders\Custom;
use App\ServerProviders\DigitalOcean;
use App\ServerProviders\Hetzner;
use App\ServerProviders\Linode;
use App\ServerProviders\Vultr;
use Illuminate\Support\ServiceProvider;

class ServerProviderServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->custom();
        $this->aws();
        $this->hetzner();
        $this->digitalOcean();
        $this->linode();
        $this->vultr();
    }

    private function custom(): void
    {
        RegisterServerProvider::make(Custom::id())
            ->label('Custom')
            ->handler(Custom::class)
            ->register();
    }

    private function aws(): void
    {
        RegisterServerProvider::make(AWS::id())
            ->label('AWS')
            ->handler(AWS::class)
            ->form(
                DynamicForm::make([
                    DynamicField::make('key')
                        ->text()
                        ->label('Access Key'),
                    DynamicField::make('secret')
                        ->text()
                        ->label('Secret'),
                ])
            )
            ->defaultUser('ubuntu')
            ->register();
    }

    private function hetzner(): void
    {
        RegisterServerProvider::make(Hetzner::id())
            ->label('Hetzner')
            ->handler(Hetzner::class)
            ->form(
                DynamicForm::make([
                    DynamicField::make('token')
                        ->text()
                        ->label('Token'),
                ])
            )
            ->defaultUser('root')
            ->register();
    }

    private function digitalOcean(): void
    {
        RegisterServerProvider::make(DigitalOcean::id())
            ->label('DigitalOcean')
            ->handler(DigitalOcean::class)
            ->form(
                DynamicForm::make([
                    DynamicField::make('token')
                        ->text()
                        ->label('Token'),
                ])
            )
            ->defaultUser('root')
            ->register();
    }

    private function linode(): void
    {
        RegisterServerProvider::make(Linode::id())
            ->label('Linode')
            ->handler(Linode::class)
            ->form(
                DynamicForm::make([
                    DynamicField::make('token')
                        ->text()
                        ->label('Token'),
                ])
            )
            ->defaultUser('root')
            ->register();
    }

    private function vultr(): void
    {
        RegisterServerProvider::make(Vultr::id())
            ->label('Vultr')
            ->handler(Vultr::class)
            ->form(
                DynamicForm::make([
                    DynamicField::make('token')
                        ->text()
                        ->label('Token'),
                ])
            )
            ->defaultUser('root')
            ->register();
    }
}
