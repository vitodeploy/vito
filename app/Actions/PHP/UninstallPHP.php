<?php

namespace App\Actions\PHP;

use App\Models\Server;
use Illuminate\Validation\ValidationException;

class UninstallPHP
{
    public function uninstall(Server $server, string $version): void
    {
        $this->validate($server, $version);

        $php = $server->services()->where('type', 'php')->where('version', $version)->first();

        $php->uninstall();
    }

    /**
     * @throws ValidationException
     */
    private function validate(Server $server, string $version): void
    {
        $php = $server->services()->where('type', 'php')->where('version', $version)->first();

        if (! $php) {
            throw ValidationException::withMessages(
                ['version' => __('This version has not been installed yet!')]
            );
        }

        $hasSite = $server->sites()->where('php_version', $version)->first();
        if ($hasSite) {
            throw ValidationException::withMessages(
                ['version' => __('Cannot uninstall this version because some sites are using it!')]
            );
        }
    }
}
