<?php

namespace App\ServerTypes;

class LoadBalancer extends AbstractType
{
    public function createRules(array $input): array
    {
        return [
            'webserver' => [
                'required',
                'in:'.implode(',', config('core.webservers')),
            ],
        ];
    }

    public function data(array $input): array
    {
        return [];
    }

    public function createServices(array $input): void
    {
        $this->server->services()->forceDelete();
        $this->addWebserver($input['webserver']);
        $this->addUfw();
    }
}
