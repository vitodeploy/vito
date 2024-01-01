<?php

namespace App\SiteTypes;

use App\Enums\SiteFeature;
use App\Jobs\Site\CloneRepository;
use App\Jobs\Site\ComposerInstall;
use App\Jobs\Site\CreateVHost;
use App\Jobs\Site\DeployKey;
use Illuminate\Support\Facades\Bus;
use Illuminate\Validation\Rule;
use Throwable;

class PHPSite extends AbstractSiteType
{
    public function language(): string
    {
        return 'php';
    }

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
            'source_control' => [
                'required',
                Rule::exists('source_controls', 'id'),
            ],
            'repository' => [
                'required',
            ],
            'branch' => [
                'required',
            ],
        ];
    }

    public function createFields(array $input): array
    {
        return [
            'web_directory' => $input['web_directory'] ?? '',
            'source_control_id' => $input['source_control'],
            'repository' => $input['repository'],
            'branch' => $input['branch'],
        ];
    }

    public function data(array $input): array
    {
        return [
            'composer' => (bool) $input['composer'],
            'php_version' => $input['php_version'],
        ];
    }

    public function install(): void
    {
        $chain = [
            new CreateVHost($this->site),
            $this->progress(15),
            new DeployKey($this->site),
            $this->progress(30),
            new CloneRepository($this->site),
            $this->progress(65),
            function () {
                $this->site->php()?->restart();
            },
        ];

        if ($this->site->type_data['composer']) {
            $chain[] = new ComposerInstall($this->site);
        }

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

    public function editValidationRules(array $input): array
    {
        return [];
    }

    public function edit(): void
    {
        //
    }
}
