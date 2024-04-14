<?php

namespace App\Actions\PHP;

use App\Models\Server;
use App\Models\Service;
use App\SSH\Services\PHP\PHP;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InstallPHPExtension
{
    public function install(Server $server, array $input): Service
    {
        $this->validate($server, $input);

        /** @var Service $service */
        $service = $server->php($input['version']);
        $typeData = $service->type_data;
        $typeData['extensions'] = $typeData['extensions'] ?? [];
        $typeData['extensions'][] = $input['extension'];
        $service->type_data = $typeData;
        $service->save();

        dispatch(function () use ($service, $input) {
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

    /**
     * @throws ValidationException
     */
    private function validate(Server $server, array $input): void
    {
        Validator::make($input, [
            'extension' => [
                'required',
                'in:'.implode(',', config('core.php_extensions')),
            ],
            'version' => [
                'required',
                Rule::in($server->installedPHPVersions()),
            ],
        ])->validate();

        /** @var Service $service */
        $service = $server->php($input['version']);

        if (in_array($input['extension'], $service->type_data['extensions'])) {
            throw ValidationException::withMessages(
                ['extension' => __('This extension already installed')]
            )->errorBag('installPHPExtension');
        }
    }
}
