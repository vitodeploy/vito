<?php

namespace App\Providers;

use App\DTOs\DynamicField;
use App\DTOs\DynamicForm;
use App\Plugins\RegisterSourceControl;
use App\SourceControlProviders\Bitbucket;
use App\SourceControlProviders\BitbucketV2;
use App\SourceControlProviders\Gitea;
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
        $this->bitbucketV2();
        $this->gitea();
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
            ->label('Bitbucket (deprecated)')
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

    private function bitbucketV2(): void
    {
        RegisterSourceControl::make(BitbucketV2::id())
            ->label('Bitbucket V2')
            ->handler(BitbucketV2::class)
            ->form(
                DynamicForm::make([
                    DynamicField::make('key')
                        ->text()
                        ->label('Key'),
                    DynamicField::make('secret')
                        ->text()
                        ->label('Secret'),
                ])
            )
            ->register();
    }

    private function gitea(): void
    {
        RegisterSourceControl::make(Gitea::id())
            ->label('Gitea')
            ->handler(Gitea::class)
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
}
