<?php

namespace App\Providers;

use App\Facades\SSH;
use App\Models\FirewallRule;
use App\Models\PersonalAccessToken;
use App\Models\Script;
use App\Models\ScriptExecution;
use App\Models\ServerLog;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

class DemoServiceProvider extends ServiceProvider
{
    protected string $error = 'Cannot modify on Demo!';

    /**
     * @var string[]
     */
    protected array $canDelete = [
        //
    ];

    /**
     * @var string[]
     */
    protected array $canUpdate = [
        ServerLog::class,
        Script::class,
        ScriptExecution::class,
    ];

    /**
     * @var string[]
     */
    protected array $canCreate = [
        ServerLog::class,
        Script::class,
        ScriptExecution::class,
        FirewallRule::class,
        PersonalAccessToken::class,
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (! config('app.demo') || app()->runningInConsole()) {
            return;
        }

        // get all classes inside App\Models namespace
        $files = scandir(app_path('Models')) ?: [];
        $models = collect($files)
            ->filter(fn ($file): bool => ! in_array($file, ['.', '..']))
            ->map(fn ($file) => 'App\\Models\\'.str_replace('.php', '', $file));

        foreach ($models as $model) {
            if (! in_array($model, $this->canCreate)) {
                $this->preventCreating($model);
            }
            if (! in_array($model, $this->canUpdate)) {
                $this->preventUpdating($model);
            }
            if (! in_array($model, $this->canDelete)) {
                $this->preventDeletion($model);
            }
        }

        SSH::fake('Demo SSH is enabled. No SSH commands will be executed.');
        Http::fake([
            '*' => Http::response([]),
        ]);

        config()->set('queue.default', 'sync');
        config()->set('logging.default');
        config()->set('session.driver', 'file');
    }

    private function preventUpdating(string $model): void
    {
        $model::updating(function ($m): void {
            $throw = true;
            if ($m instanceof User && ! $m->isDirty(['name', 'email', 'password', 'two_factor_secret', 'two_factor_recovery_codes'])) {
                $throw = false;
            }
            if ($throw) {
                abort(403, $this->error);
            }
        });
    }

    private function preventDeletion(string $model): void
    {
        $model::deleting(function ($m): void {
            abort(403, $this->error);
        });
    }

    private function preventCreating(string $model): void
    {
        $model::creating(function ($m): void {
            abort(403, $this->error);
        });
    }
}
