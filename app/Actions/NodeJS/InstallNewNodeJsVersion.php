<?php

namespace App\Actions\NodeJS;

use App\Enums\NodeJS;
use App\Enums\ServiceStatus;
use App\Models\Server;
use App\Models\Service;
use Illuminate\Validation\Rule;

class InstallNewNodeJsVersion
{
    public function install(Server $server, array $input): void
    {
        $nodejs = new Service([
            'server_id' => $server->id,
            'type' => 'nodejs',
            'type_data' => [],
            'name' => 'nodejs',
            'version' => $input['version'],
            'status' => ServiceStatus::INSTALLING,
            'is_default' => false,
        ]);
        $nodejs->save();

        dispatch(function () use ($nodejs) {
            $nodejs->handler()->install();
            $nodejs->status = ServiceStatus::READY;
            $nodejs->save();
        })->catch(function () use ($nodejs) {
            $nodejs->delete();
        })->onConnection('ssh');
    }

    public static function rules(Server $server): array
    {
        return [
            'version' => [
                'required',
                Rule::in(config('core.nodejs_versions')),
                Rule::notIn(array_merge($server->installedNodejsVersions(), [NodeJS::NONE])),
            ],
        ];
    }
}
