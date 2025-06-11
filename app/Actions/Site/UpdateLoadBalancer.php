<?php

namespace App\Actions\Site;

use App\Enums\LoadBalancerMethod;
use App\Models\LoadBalancerServer;
use App\Models\Site;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UpdateLoadBalancer
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function update(Site $site, array $input): void
    {
        Validator::make($input, self::rules($site))->validate();

        $site->loadBalancerServers()->delete();

        foreach ($input['servers'] as $server) {
            $loadBalancerServer = new LoadBalancerServer([
                'load_balancer_id' => $site->id,
                'ip' => $server['ip'],
                'port' => $server['port'],
                'weight' => $server['weight'],
                'backup' => (bool) $server['backup'],
            ]);
            $loadBalancerServer->save();
        }

        $site->webserver()->updateVHost($site, regenerate: [
            'load-balancer-upstream',
            'load-balancer',
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(Site $site): array
    {
        return [
            'servers' => [
                'required',
                'array',
            ],
            'servers.*.ip' => [
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
