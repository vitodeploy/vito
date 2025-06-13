<?php

namespace App\Actions\NodeJS;

use App\Enums\ServiceStatus;
use App\Exceptions\SSHError;
use App\Models\Server;
use App\Models\Service;
use App\Services\NodeJS\NodeJS;
use Illuminate\Validation\ValidationException;

class ChangeDefaultCli
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     * @throws SSHError
     */
    public function change(Server $server, array $input): void
    {
        $this->validate($server, $input);
        /** @var Service $service */
        $service = $server->nodejs($input['version']);
        /** @var NodeJS $handler */
        $handler = $service->handler();
        $handler->setDefaultCli();
        $server->defaultService('nodejs')?->update(['is_default' => 0]);
        $service->update(['is_default' => 1]);
        $service->update(['status' => ServiceStatus::READY]);
    }

    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function validate(Server $server, array $input): void
    {
        if (! isset($input['version']) || ! in_array($input['version'], $server->installedNodejsVersions())) {
            throw ValidationException::withMessages(
                ['version' => __('This version is not installed')]
            );
        }
    }
}
