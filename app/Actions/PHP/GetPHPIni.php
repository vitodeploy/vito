<?php

namespace App\Actions\PHP;

use App\Models\Server;
use App\SSH\Services\PHP\PHP;
use Illuminate\Validation\ValidationException;

class GetPHPIni
{
    public function getIni(Server $server, array $input): string
    {
        $this->validate($server, $input);

        $php = $server->php($input['version']);

        try {
            /** @var PHP $handler */
            $handler = $php->handler();

            return $handler->getPHPIni();
        } catch (\Throwable $e) {
            throw ValidationException::withMessages(
                ['ini' => $e->getMessage()]
            );
        }
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
