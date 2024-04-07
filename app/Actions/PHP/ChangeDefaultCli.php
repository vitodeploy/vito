<?php

namespace App\Actions\PHP;

use App\Enums\ServiceStatus;
use App\Models\Server;
use App\SSH\Services\PHP\PHP;
use Illuminate\Validation\ValidationException;

class ChangeDefaultCli
{
    public function change(Server $server, array $input): void
    {
        $this->validate($server, $input);
        $service = $server->php($input['version']);
        /** @var PHP $handler */
        $handler = $service->handler();
        $handler->setDefaultCli();
        $server->defaultService('php')->update(['is_default' => 0]);
        $service->update(['is_default' => 1]);
        $service->update(['status' => ServiceStatus::READY]);
    }

    public function validate(Server $server, array $input): void
    {
        if (! isset($input['version']) || ! in_array($input['version'], $server->installedPHPVersions())) {
            throw ValidationException::withMessages(
                ['version' => __('This version is not installed')]
            );
        }
    }
}
