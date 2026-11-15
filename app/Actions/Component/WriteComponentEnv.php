<?php

namespace App\Actions\Component;

use App\Exceptions\SSHError;
use App\Helpers\ComponentVariables;
use App\Helpers\EnvParser;
use App\Models\Component;
use Illuminate\Support\Str;
use Throwable;

class WriteComponentEnv
{
    /**
     * @throws SSHError
     */
    public function write(Component $component, string $path): void
    {
        $variables = [];
        foreach (ComponentVariables::resolve($component) as $key => $variable) {
            $variables[] = ['key' => $key, 'value' => $variable['value']];
        }
        $loader = $component->basePath().'/vito-env-exec';
        $suffix = '.'.Str::random(16);
        $data = ['path' => $path, 'loader' => $loader, 'envTemp' => $path.$suffix, 'loaderTemp' => $loader.$suffix];
        try {
            $component->server->ssh($component->user)->exec(view('ssh.applications.write-env', [...$data, 'operation' => 'prepare']));
            $component->server->os()->write($data['loaderTemp'], view('ssh.applications.env-exec')->render(), $component->user);
            $component->server->os()->write($data['envTemp'], EnvParser::stringifyLiteral($variables), $component->user);
            $component->server->ssh($component->user)->exec(view('ssh.applications.write-env', [...$data, 'operation' => 'install']));
        } catch (Throwable $e) {
            try {
                $component->server->ssh($component->user)->exec(view('ssh.applications.write-env', [...$data, 'operation' => 'cleanup']));
            } catch (Throwable) {
            }
            throw $e;
        }
    }
}
