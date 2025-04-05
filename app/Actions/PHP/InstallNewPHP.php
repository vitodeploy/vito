<?php

namespace App\Actions\PHP;

use App\Enums\PHP;
use App\Enums\ServiceStatus;
use App\Models\Server;
use App\Models\Service;
use Illuminate\Validation\Rule;

class InstallNewPHP
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function install(Server $server, array $input): void
    {
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

        dispatch(function () use ($php): void {
            $php->handler()->install();
            $php->status = ServiceStatus::READY;
            $php->save();
        })->catch(function () use ($php): void {
            $php->delete();
        })->onConnection('ssh');
    }

    /**
     * @return array<string, array<string>>
     */
    public static function rules(Server $server): array
    {
        return [
            'version' => [
                'required',
                Rule::in(config('core.php_versions')),
                Rule::notIn(array_merge($server->installedPHPVersions(), [PHP::NONE])),
            ],
        ];
    }
}
