<?php

namespace App\ServerTypes;

class Regular extends AbstractType
{
    public function createRules(array $input): array
    {
        return [
            'webserver' => [
                'required',
                'in:'.implode(',', config('core.webservers')),
            ],
            'php' => [
                'required',
                'in:none,'.implode(',', config('core.php_versions')),
            ],
            'database' => [
                'required',
                'in:'.implode(',', config('core.databases')),
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
