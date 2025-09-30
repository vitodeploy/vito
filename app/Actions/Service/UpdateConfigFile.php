<?php

namespace App\Actions\Service;

use App\Models\Service;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class UpdateConfigFile
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function update(Service $service, array $input): void
    {
        $this->validate($service, $input);

        $configPaths = config("service.services.{$service->name}.config_paths", []);
        $configPath = collect($configPaths)->firstWhere('name', $input['config_name']);

        if (! $configPath) {
            throw ValidationException::withMessages([
                'config_name' => __('Config file not found'),
            ]);
        }

        $tmpName = Str::random(10).strtotime('now');

        try {
            /** @var FilesystemAdapter $storageDisk */
            $storageDisk = Storage::disk('local');

            $storageDisk->put($tmpName, $input['content']);

            $path = str_replace('{version}', $service->version, $configPath['path']);
            $sudo = $configPath['sudo'] ?? false;

            $service->server->ssh('root')->upload(
                $storageDisk->path($tmpName),
                $path
            );

            if ($sudo) {
                $service->server->ssh('root')->exec('sudo chown root:root '.escapeshellarg($path));
                $service->server->ssh('root')->exec('sudo chmod 644 '.escapeshellarg($path));
            }

            $this->deleteTempFile($tmpName);
        } catch (Throwable) {
            $this->deleteTempFile($tmpName);
            throw ValidationException::withMessages([
                'content' => __("Couldn't update config file!"),
            ]);
        }

        $service->restart();
    }

    private function deleteTempFile(string $name): void
    {
        if (Storage::disk('local')->exists($name)) {
            Storage::disk('local')->delete($name);
        }
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function validate(Service $service, array $input): void
    {
        $rules = [
            'config_name' => [
                'required',
                'string',
            ],
            'content' => [
                'required',
                'string',
            ],
        ];

        Validator::make($input, $rules)->validate();

        $configPaths = config("service.services.{$service->name}.config_paths", []);
        if (empty($configPaths)) {
            throw ValidationException::withMessages([
                'config_paths' => __('This service has no config files defined'),
            ]);
        }
    }
}
