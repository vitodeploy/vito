<?php

namespace App\Actions\Service;

use App\Models\Service;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

class GetConfigFile
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function get(Service $service, array $input): string
    {
        $this->validate($service, $input);

        $configPaths = config("service.services.{$service->name}.config_paths", []);
        $configPath = collect($configPaths)->firstWhere('name', $input['config_name']);

        if (! $configPath) {
            throw ValidationException::withMessages([
                'config_name' => __('Config file not found'),
            ]);
        }

        try {
            $path = str_replace('{version}', $service->version, $configPath['path']);
            $sudo = $configPath['sudo'] ?? false;

            $command = $sudo ? 'sudo cat '.escapeshellarg($path) : 'cat '.escapeshellarg($path);

            return $service->server->ssh()->exec($command);
        } catch (Throwable $e) {
            throw ValidationException::withMessages([
                'config' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    private function validate(Service $service, array $input): void
    {
        Validator::make($input, [
            'config_name' => [
                'required',
                'string',
            ],
        ])->validate();

        $configPaths = config("service.services.{$service->name}.config_paths", []);
        if (empty($configPaths)) {
            throw ValidationException::withMessages([
                'config_paths' => __('This service has no config files defined'),
            ]);
        }
    }
}
