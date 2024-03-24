<?php

namespace App\Actions\PHP;

use App\Enums\ServiceStatus;
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
        $php->status = ServiceStatus::UNINSTALLING;
        $php->save();

        dispatch(function () use ($php) {
            $php->handler()->uninstall();
            $php->delete();
        })->catch(function () use ($php) {
            $php->status = ServiceStatus::FAILED;
            $php->save();
        })->onConnection('ssh');
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
