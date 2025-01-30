<?php

namespace App\SiteTypes;

use App\Enums\LoadBalancerMethod;
use App\Enums\SiteFeature;
use App\Exceptions\SSHError;
use App\SSH\Services\Webserver\Webserver;
use Illuminate\Validation\Rule;

class LoadBalancer extends AbstractSiteType
{
    public function language(): string
    {
        return 'yaml';
    }

    public function supportedFeatures(): array
    {
        return [
            SiteFeature::SSL,
        ];
    }

    public function createRules(array $input): array
    {
        return [
            'method' => [
                'required',
                Rule::in(LoadBalancerMethod::all()),
            ],
        ];
    }

    public function data(array $input): array
    {
        return [
            'method' => $input['method'] ?? LoadBalancerMethod::ROUND_ROBIN,
        ];
    }

    /**
     * @throws SSHError
     */
    public function install(): void
    {
        $this->isolate();

        /** @var Webserver $webserver */
        $webserver = $this->site->server->webserver()->handler();
        $webserver->createVHost($this->site);
    }

    public function edit(): void
    {
        //
    }
}
