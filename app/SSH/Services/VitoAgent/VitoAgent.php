<?php

namespace App\SSH\Services\VitoAgent;

use App\SSH\Services\AbstractService;

class VitoAgent extends AbstractService
{
    const TAGS_URL = 'https://api.github.com/repos/vitodeploy/agent/tags';

    public function creationRules(array $input): array
    {
        return [

        ];
    }

    public function creationData(array $input): array
    {
        return [

        ];
    }

    public function data(): array
    {
        return [];
    }

    public function install(): void
    {

    }

    public function uninstall(): void
    {
        //
    }
}
