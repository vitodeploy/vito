<?php

namespace App\Actions\NodeJS;

use App\Enums\NodeJS;
use App\Enums\ServiceStatus;
use App\Models\Server;
use App\Models\Service;
use Illuminate\Validation\Rule;

class InstallNewNodeJsVersion
{
    /**
     * @param  array<string, mixed>  $input
     */
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

        dispatch(function () use ($nodejs): void {
            $nodejs->handler()->install();
            $nodejs->status = ServiceStatus::READY;
            $nodejs->save();
        })->catch(function () use ($nodejs): void {
            $nodejs->delete();
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
                Rule::in(config('core.nodejs_versions')),
                Rule::notIn(array_merge($server->installedNodejsVersions(), [NodeJS::NONE])),
            ],
        ];
    }
}
