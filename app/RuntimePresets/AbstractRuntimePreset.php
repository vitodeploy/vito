<?php

namespace App\RuntimePresets;

use App\DTOs\DynamicForm;
use App\Enums\WebMode;
use Illuminate\Support\Facades\File;

abstract class AbstractRuntimePreset
{
    abstract public static function id(): string;

    abstract public static function label(): string;

    abstract public function webMode(): WebMode;

    /**
     * @return list<string>
     */
    public function versions(): array
    {
        return [];
    }

    /**
     * @return array<string, ?string>
     */
    public function requiredServices(string $version): array
    {
        return [];
    }

    public function tooling(): ?string
    {
        return null;
    }

    public function webRoot(): ?string
    {
        return null;
    }

    public function startCommand(): ?string
    {
        return null;
    }

    public function schedulerCommand(): ?string
    {
        return null;
    }

    /**
     * @return list<string>
     */
    public function sharedPaths(): array
    {
        return [];
    }

    /**
     * @return array{build: string, pre-release: string, post-release: string}
     */
    public function defaultScripts(): array
    {
        $scripts = ['build' => '', 'pre-release' => '', 'post-release' => ''];

        foreach ($scripts as $stage => $script) {
            $path = resource_path('deployment-scripts/presets/'.static::id().'/'.$stage.'.sh');
            $scripts[$stage] = File::exists($path) ? File::get($path) : '';
        }

        return $scripts;
    }

    public function form(): DynamicForm
    {
        return DynamicForm::make([]);
    }
}
