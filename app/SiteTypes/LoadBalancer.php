<?php

namespace App\SiteTypes;

use App\Enums\LoadBalancerMethod;
use App\Enums\SiteFeature;
use App\Exceptions\SSHError;
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

        $this->site->webserver()->createVHost($this->site);
    }

    public function edit(): void
    {
        //
    }
}
