<?php

namespace App\Actions\Database;

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
            if ($phpMyAdmin->status === 'ready') {
                throw ValidationException::withMessages([
                    'install' => __('Already installed'),
                ])->errorBag('installPHPMyAdmin');
            }
            $phpMyAdmin->delete();
        }
        $phpMyAdmin = new Service([
            'server_id' => $server->id,
            'type' => 'phpmyadmin',
            'type_data' => [
                'allowed_ip' => $input['allowed_ip'],
                'php' => $server->defaultService('php')->version,
            ],
            'name' => 'phpmyadmin',
            'version' => '5.1.2',
            'status' => 'installing',
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
        ])->validateWithBag('installPHPMyAdmin');
    }
}
