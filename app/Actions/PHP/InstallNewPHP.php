<?php

namespace App\Actions\PHP;

use App\Enums\ServiceStatus;
use App\Models\Server;
use App\Models\Service;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InstallNewPHP
{
    public function install(Server $server, array $input): void
    {
        $this->validate($server, $input);

        $php = new Service([
            'server_id' => $server->id,
            'type' => 'php',
            'type_data' => [
                'extensions' => [],
                'settings' => config('core.php_settings'),
            ],
            'name' => 'php',
            'version' => $input['version'],
            'status' => ServiceStatus::INSTALLING,
            'is_default' => false,
        ]);
        $php->save();

        dispatch(function () use ($php) {
            $php->handler()->install();
            $php->status = ServiceStatus::READY;
            $php->save();
        })->catch(function () use ($php) {
            $php->delete();
        })->onConnection('ssh');
    }

    /**
     * @throws ValidationException
     */
    private function validate(Server $server, array $input): void
    {
        Validator::make($input, [
            'version' => [
                'required',
                Rule::in(config('core.php_versions')),
            ],
        ])->validate();

        if (in_array($input['version'], $server->installedPHPVersions())) {
            throw ValidationException::withMessages(
                ['version' => __('This version is already installed')]
            );
        }
    }
}
