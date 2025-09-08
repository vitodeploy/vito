<?php

namespace App\Actions\PHP;

use App\Models\Server;
use App\Models\Service;
use App\Services\PHP\PHP;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InstallPHPExtension
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function install(Server $server, array $input): Service
    {
        /** @var Service $service */
        $service = $server->php($input['version']);

        $this->validate($server, $service, $input);

        if (in_array($input['extension'], $service->type_data['extensions'] ?? [])) {
            throw ValidationException::withMessages([
                'extension' => 'The extension is already installed.',
            ]);
        }

        $typeData = $service->type_data;
        $typeData['extensions'] ??= [];
        $typeData['extensions'][] = $input['extension'];
        $service->type_data = $typeData;
        $service->save();

        dispatch(
            function () use ($service, $input): void {
                /** @var PHP $handler */
                $handler = $service->handler();
                $handler->installExtension($input['extension']);
            })->catch(function () use ($service, $input): void {
                $service->refresh();
                $typeData = $service->type_data;
                $typeData['extensions'] = array_values(array_diff($typeData['extensions'], [$input['extension']]));
                $service->type_data = $typeData;
                $service->save();
            })->onQueue('ssh-unique');

        return $service;
    }

    private function validate(Server $server, Service $service, array $input): void
    {
        $extensions = event('php.extensions.list', [
            'service' => $service,
            'available_extensions' => [],
        ]);
        $extensions = array_shift($extensions);

        $rules = [
            'extension' => [
                'required',
                Rule::in($extensions['available_extensions'] ?? config('service.services.php.data.extensions', [])),
            ],
            'version' => [
                'required',
                Rule::exists('services', 'version')
                    ->where('server_id', $server->id)
                    ->where('type', 'php'),
            ],
        ];

        Validator::make($input, $rules)->validate();
    }
}
