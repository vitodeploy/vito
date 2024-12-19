<?php

namespace App\Actions\NodeJS;

use App\Enums\NodeJS;
use App\Enums\ServiceStatus;
use App\Models\Server;
use App\Models\Service;
use Illuminate\Validation\Rule;

class InstallNewNode
{
    public function install(Server $server, array $input): void
    {
        $node = new Service([
            'server_id' => $server->id,
            'type' => 'nodejs',
            'type_data' => [],
            'name' => 'nodejs',
            'version' => $input['version'],
            'status' => ServiceStatus::INSTALLING,
            'is_default' => false,
        ]);
        $node->save();

        dispatch(function () use ($node) {
            $node->handler()->install();
            $node->status = ServiceStatus::READY;
            $node->save();
        })->catch(function () use ($node) {
            $node->delete();
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
