<?php

namespace App\Actions\NodeJS;

use App\Enums\ServiceStatus;
use App\Models\Server;
use App\Models\Service;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UninstallNodeJS
{
    public function uninstall(Server $server, array $input): void
    {
        $this->validate($server, $input);

        /** @var Service $nodejs */
        $nodejs = $server->nodejs($input['version']);
        $nodejs->status = ServiceStatus::UNINSTALLING;
        $nodejs->save();

        dispatch(function () use ($nodejs) {
            $nodejs->handler()->uninstall();
            $nodejs->delete();
        })->catch(function () use ($nodejs) {
            $nodejs->status = ServiceStatus::FAILED;
            $nodejs->save();
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

        if (! in_array($input['version'], $server->installedNodejsVersions())) {
            throw ValidationException::withMessages(
                ['version' => __('This version is not installed')]
            );
        }

        $hasSite = $server->sites()->where('nodejs_version', $input['version'])->first();
        if ($hasSite) {
            throw ValidationException::withMessages(
                ['version' => __('Cannot uninstall this version because some sites are using it!')]
            );
        }
    }
}
