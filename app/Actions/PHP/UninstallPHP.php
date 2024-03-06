<?php

namespace App\Actions\PHP;

use App\Models\Server;
use App\Models\Service;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UninstallPHP
{
    public function uninstall(Server $server, array $input): void
    {
        $this->validate($server, $input);

        /** @var Service $php */
        $php = $server->php($input['version']);

        $php->uninstall();
    }

    /**
     * @throws ValidationException
     */
    private function validate(Server $server, array $input): void
    {
        Validator::make($input, [
            'version' => 'required|string',
        ])->validate();

        if (! in_array($input['version'], $server->installedPHPVersions())) {
            throw ValidationException::withMessages(
                ['version' => __('This version is not installed')]
            );
        }

        $hasSite = $server->sites()->where('php_version', $input['version'])->first();
        if ($hasSite) {
            throw ValidationException::withMessages(
                ['version' => __('Cannot uninstall this version because some sites are using it!')]
            );
        }
    }
}
