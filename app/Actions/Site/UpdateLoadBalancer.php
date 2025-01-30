<?php

namespace App\Actions\Site;

use App\Enums\LoadBalancerMethod;
use App\Models\LoadBalancerServer;
use App\Models\Site;
use Illuminate\Validation\Rule;

class UpdateLoadBalancer
{
    public function update(Site $site, array $input): void
    {
        $site->loadBalancerServers()->delete();

        foreach ($input['servers'] as $server) {
            $loadBalancerServer = new LoadBalancerServer([
                'load_balancer_id' => $site->id,
                'ip' => $server['server'],
                'port' => $server['port'],
                'weight' => $server['weight'],
                'backup' => (bool) $server['backup'],
            ]);
            $loadBalancerServer->save();
        }

        $site->webserver()->updateVHost($site);
    }

    public static function rules(Site $site): array
    {
        return [
            'servers' => [
                'required',
                'array',
            ],
            'servers.*.server' => [
                'required',
                Rule::exists('servers', 'local_ip')
                    ->where('project_id', $site->project->id),
            ],
            'servers.*.port' => [
                'required',
                'numeric',
                'min:1',
                'max:65535',
            ],
            'servers.*.weight' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'servers.*.backup' => [
                'required',
                'boolean',
            ],
            'method' => [
                'required',
                Rule::in(LoadBalancerMethod::all()),
            ],
        ];
    }
}
