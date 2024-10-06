<?php

namespace App\Actions\PHP;

use App\Exceptions\SSHCommandError;
use App\Models\Server;
use App\Models\Service;
use App\SSH\Services\PHP\PHP;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InstallPHPExtension
{
    public function install(Server $server, array $input): Service
    {
        /** @var Service $service */
        $service = $server->php($input['version']);

        if (in_array($input['extension'], $service->type_data['extensions'] ?? [])) {
            throw ValidationException::withMessages([
                'extension' => 'The extension is already installed.',
            ]);
        }

        $typeData = $service->type_data;
        $typeData['extensions'] = $typeData['extensions'] ?? [];
        $typeData['extensions'][] = $input['extension'];
        $service->type_data = $typeData;
        $service->save();

        dispatch(
            /**
             * @throws SSHCommandError
             */
            function () use ($service, $input) {
                /** @var PHP $handler */
                $handler = $service->handler();
                $handler->installExtension($input['extension']);
            })->catch(function () use ($service, $input) {
                $service->refresh();
                $typeData = $service->type_data;
                $typeData['extensions'] = array_values(array_diff($typeData['extensions'], [$input['extension']]));
                $service->type_data = $typeData;
                $service->save();
            })->onConnection('ssh');

        return $service;
    }

    public static function rules(Server $server): array
    {
        return [
            'extension' => [
                'required',
                Rule::in(config('core.php_extensions')),
            ],
            'version' => [
                'required',
                Rule::exists('services', 'version')
                    ->where('server_id', $server->id)
                    ->where('type', 'php'),
            ],
        ];
    }
}
