<?php

namespace App\Actions\Site;

use App\Exceptions\SSHError;
use App\Helpers\EnvParser;
use App\Models\Site;
use Illuminate\Validation\ValidationException;

class GetEnv
{
    /**
     * @return array{env?: string, variables: array<int, array{key: string, value: string, is_secret: bool}>}
     * @throws SSHError
     * @throws ValidationException
     */
    public function get(Site $site, ?string $path = null, bool $reveal = false): array
    {
        $env = $site->server->os()->readFile($site->resolveEnvPath($path));

        $parsed = EnvParser::parse($env);
        $variables = EnvParser::classify($parsed, $site->env_variables);

        if (! $reveal) {
            return [
                'variables' => EnvParser::isRepresentable($env, $parsed) ? EnvParser::maskSecrets($variables) : [],
            ];
        }

        return [
            'env' => $env,
            'variables' => $variables,
        ];
    }
}
