<?php

namespace App\SiteTypes;

use App\Enums\SiteFeature;
use App\Jobs\Site\CreateVHost;
use Illuminate\Support\Facades\Bus;
use Illuminate\Validation\Rule;
use Throwable;

class PHPBlank extends PHPSite
{
    public function supportedFeatures(): array
    {
        return [
            SiteFeature::DEPLOYMENT,
            SiteFeature::ENV,
            SiteFeature::SSL,
            SiteFeature::QUEUES,
        ];
    }

    public function createValidationRules(array $input): array
    {
        return [
            'php_version' => [
                'required',
                Rule::in($this->site->server->installedPHPVersions()),
            ],
        ];
    }

    public function createFields(array $input): array
    {
        return [
            'web_directory' => $input['web_directory'] ?? '',
            'php_version' => $input['php_version'] ?? '',
        ];
    }

    public function data(array $input): array
    {
        return [];
    }

    public function install(): void
    {
        $chain = [
            new CreateVHost($this->site),
            $this->progress(65),
            function () {
                $this->site->php()?->restart();
            },
        ];

        $chain[] = function () {
            $this->site->installationFinished();
        };

        Bus::chain($chain)
            ->catch(function (Throwable $e) {
                $this->site->installationFailed($e);
            })
            ->onConnection('ssh-long')
            ->dispatch();
    }
}
