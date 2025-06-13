<?php

namespace App\Providers;

use App\DTOs\DynamicField;
use App\DTOs\DynamicForm;
use App\Plugins\RegisterSourceControl;
use App\SourceControlProviders\Bitbucket;
use App\SourceControlProviders\Github;
use App\SourceControlProviders\Gitlab;
use Illuminate\Support\ServiceProvider;

class SourceControlServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->github();
        $this->gitlab();
        $this->bitbucket();
    }

    private function github(): void
    {
        RegisterSourceControl::make(Github::id())
            ->label('Github')
            ->handler(Github::class)
            ->form(
                DynamicForm::make([
                    DynamicField::make('token')
                        ->text()
                        ->label('Token'),
                ])
            )
            ->register();
    }

    private function gitlab(): void
    {
        RegisterSourceControl::make(Gitlab::id())
            ->label('Gitlab')
            ->handler(Gitlab::class)
            ->form(
                DynamicForm::make([
                    DynamicField::make('token')
                        ->text()
                        ->label('Token'),
                    DynamicField::make('url')
                        ->text()
                        ->label('Self hosted URL'),
                ])
            )
            ->register();
    }

    private function bitbucket(): void
    {
        RegisterSourceControl::make(Bitbucket::id())
            ->label('Bitbucket')
            ->handler(Bitbucket::class)
            ->form(
                DynamicForm::make([
                    DynamicField::make('username')
                        ->text()
                        ->label('Username'),
                    DynamicField::make('password')
                        ->text()
                        ->label('Password'),
                ])
            )
            ->register();
    }
}
