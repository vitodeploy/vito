<?php

namespace App\Actions\Node;

use App\Enums\Node;
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
            'type' => 'node',
            'type_data' => [],
            'name' => 'node',
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
                Rule::in(config('core.node_versions')),
                Rule::notIn(array_merge($server->installedNodeVersions(), [Node::NONE])),
            ],
        ];
    }
}
