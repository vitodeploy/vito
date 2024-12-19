<?php

namespace App\Actions\Node;

use App\Enums\ServiceStatus;
use App\Models\Server;
use App\Models\Service;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UninstallNode
{
    public function uninstall(Server $server, array $input): void
    {
        $this->validate($server, $input);

        /** @var Service $node */
        $node = $server->node($input['version']);
        $node->status = ServiceStatus::UNINSTALLING;
        $node->save();

        dispatch(function () use ($node) {
            $node->handler()->uninstall();
            $node->delete();
        })->catch(function () use ($node) {
            $node->status = ServiceStatus::FAILED;
            $node->save();
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

        if (! in_array($input['version'], $server->installedNodeVersions())) {
            throw ValidationException::withMessages(
                ['version' => __('This version is not installed')]
            );
        }

        $hasSite = $server->sites()->where('node_version', $input['version'])->first();
        if ($hasSite) {
            throw ValidationException::withMessages(
                ['version' => __('Cannot uninstall this version because some sites are using it!')]
            );
        }
    }
}
