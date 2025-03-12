<?php

namespace App\Actions\PHP;

use App\Models\Server;
use App\Models\Service;
use App\SSH\Services\PHP\PHP;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InstallPHPExtension
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
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
            })->onConnection('ssh');

        return $service;
    }

    /**
     * @return array<string, array<string>>
     */
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
