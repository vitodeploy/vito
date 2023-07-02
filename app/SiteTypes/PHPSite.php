<?php

namespace App\SiteTypes;

use App\Enums\SiteStatus;
use App\Events\Broadcast;
use App\Jobs\Site\CloneRepository;
use App\Jobs\Site\ComposerInstall;
use App\Jobs\Site\CreateVHost;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Throwable;

class PHPSite extends AbstractSiteType
{
    public function language(): string
    {
        return 'php';
    }

    public function createValidationRules(array $input): array
    {
        return [
            'php_version' => [
                'required',
                'in:'.implode(',', $this->site->server->installedPHPVersions()),
            ],
            'source_control' => [
                'required',
                Rule::exists('source_controls', 'provider'),
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
            'source_control' => $input['source_control'] ?? '',
            'repository' => $input['repository'] ?? '',
            'branch' => $input['branch'] ?? '',
        ];
    }

    public function data(array $input): array
    {
        return [
            'composer' => (bool) $input['composer'],
        ];
    }

    public function install(): void
    {
        $chain = [
            new CreateVHost($this->site),
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
            $this->site->update([
                'status' => SiteStatus::READY,
                'progress' => 100,
            ]);
            event(
                new Broadcast('install-site-finished', [
                    'site' => $this->site,
                ])
            );
            /** @todo notify */
        };

        Bus::chain($chain)
            ->catch(function (Throwable $e) {
                $this->site->update([
                    'status' => SiteStatus::INSTALLATION_FAILED,
                ]);
                event(
                    new Broadcast('install-site-failed', [
                        'site' => $this->site,
                    ])
                );
                /** @todo notify */
                Log::error('install-site-error', [
                    'error' => (string) $e,
                ]);
                throw $e;
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
