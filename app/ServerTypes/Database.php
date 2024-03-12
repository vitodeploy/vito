<?php

namespace App\ServerTypes;

class Database extends AbstractType
{
    public function createRules(array $input): array
    {
        return [
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
        $this->addDatabase($input['database']);
        $this->addSupervisor();
        $this->addRedis();
        $this->addUfw();
    }
}
