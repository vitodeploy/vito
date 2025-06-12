<?php

namespace App\Providers;

use App\DTOs\DynamicField;
use App\DTOs\DynamicForm;
use App\Enums\OperatingSystem;
use App\Plugins\RegisterServerProvider;
use App\ServerProviders\AWS;
use App\ServerProviders\Custom;
use App\ServerProviders\DigitalOcean;
use App\ServerProviders\Hetzner;
use App\ServerProviders\Linode;
use App\ServerProviders\Vultr;
use Illuminate\Support\ServiceProvider;

class ServerServiceProvider extends ServiceProvider
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
        RegisterServerProvider::make('custom')
            ->label('Custom')
            ->handler(Custom::class)
            ->register();
    }

    private function aws(): void
    {
        RegisterServerProvider::make('aws')
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
            ->defaultUsers([
                OperatingSystem::UBUNTU20 => 'ubuntu',
                OperatingSystem::UBUNTU22 => 'ubuntu',
                OperatingSystem::UBUNTU24 => 'ubuntu',
            ])
            ->register();
    }

    private function hetzner(): void
    {
        RegisterServerProvider::make('hetzner')
            ->label('Hetzner')
            ->handler(Hetzner::class)
            ->form(
                DynamicForm::make([
                    DynamicField::make('token')
                        ->text()
                        ->label('Token'),
                ])
            )
            ->register();
    }

    private function digitalOcean(): void
    {
        RegisterServerProvider::make('digitalocean')
            ->label('DigitalOcean')
            ->handler(DigitalOcean::class)
            ->form(
                DynamicForm::make([
                    DynamicField::make('token')
                        ->text()
                        ->label('Token'),
                ])
            )
            ->register();
    }

    private function linode(): void
    {
        RegisterServerProvider::make('linode')
            ->label('Linode')
            ->handler(Linode::class)
            ->form(
                DynamicForm::make([
                    DynamicField::make('token')
                        ->text()
                        ->label('Token'),
                ])
            )
            ->register();
    }

    private function vultr(): void
    {
        RegisterServerProvider::make('vultr')
            ->label('Vultr')
            ->handler(Vultr::class)
            ->form(
                DynamicForm::make([
                    DynamicField::make('token')
                        ->text()
                        ->label('Token'),
                ])
            )
            ->register();
    }
}
