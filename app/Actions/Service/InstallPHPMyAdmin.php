<?php

namespace App\Actions\Service;

use App\Enums\ServiceStatus;
use App\Models\Server;
use App\Models\Service;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class InstallPHPMyAdmin
{
    /**
     * @throws ValidationException
     */
    public function install(Server $server, array $input): Service
    {
        $this->validate($input);

        $phpMyAdmin = $server->defaultService('phpmyadmin');
        if ($phpMyAdmin) {
            throw ValidationException::withMessages([
                'allowed_ip' => __('Already installed'),
            ]);
        }
        $phpMyAdmin = new Service([
            'server_id' => $server->id,
            'type' => 'phpmyadmin',
            'type_data' => [
                'allowed_ip' => $input['allowed_ip'],
                'port' => $input['port'],
                'php' => $server->defaultService('php')->version,
            ],
            'name' => 'phpmyadmin',
            'version' => '5.1.2',
            'status' => ServiceStatus::INSTALLING,
            'is_default' => 1,
        ]);
        $phpMyAdmin->save();
        $phpMyAdmin->install();

        return $phpMyAdmin;
    }

    /**
     * @throws ValidationException
     */
    private function validate(array $input): void
    {
        Validator::make($input, [
            'allowed_ip' => 'required',
            'port' => [
                'required',
                'numeric',
                'min:1',
                'max:65535',
            ],
        ])->validate();
    }
}
