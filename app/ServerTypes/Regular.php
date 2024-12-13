<?php

namespace App\ServerTypes;

use Illuminate\Validation\Rule;

class Regular extends AbstractType
{
    public function createRules(array $input): array
    {
        return [
            'webserver' => [
                'required',
                Rule::in(config('core.webservers')),

            ],
            'php' => [
                'required',
                Rule::in(config('core.php_versions')),
            ],
            'database' => [
                'required',
                Rule::in(config('core.databases')),
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
        $this->addDatabase($input['database']);
        $this->addPHP($input['php']);
        $this->addSupervisor();
        $this->addRedis();
        $this->addUfw();
    }
}
