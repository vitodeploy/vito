<?php

namespace App\Actions\PHP;

use App\Models\Server;
use App\SSHCommands\PHP\GetPHPIniCommand;
use Illuminate\Validation\ValidationException;

class GetPHPIni
{
    public function getIni(Server $server, array $input): string
    {
        $this->validate($server, $input);

        try {
            return $server->ssh()->exec(new GetPHPIniCommand($input['version']));
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
