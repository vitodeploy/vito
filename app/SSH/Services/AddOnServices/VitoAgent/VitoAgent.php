<?php

namespace App\SSH\Services\AddOnServices\VitoAgent;

use App\SSH\Services\AddOnServices\AbstractAddOnService;

class VitoAgent extends AbstractAddOnService
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

    public function create(): void
    {

    }

    public function delete(): void
    {

    }

    public function data(): array
    {
        return [];
    }

    public function install(): void
    {

    }
}
