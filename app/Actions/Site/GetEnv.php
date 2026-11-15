<?php

namespace App\Actions\Site;

use App\Exceptions\SSHError;
use App\Helpers\EnvParser;
use App\Models\Site;
use Illuminate\Validation\ValidationException;

class GetEnv
{
    /**
     * Read the live .env file and return its variables classified against the
     * persisted secret keys. Raw content and real secret values are returned
     * only when the caller is allowed to write them back; everyone else gets
     * masked variables and no raw content at all. A file the line-based parser
     * cannot represent (a quoted value spanning lines, say) could split a secret
     * into unmasked entries, so those callers get no variables for it.
     *
     * @return array{env?: string, variables: array<int, array{key: string, value: string, is_secret: bool}>}
     *
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
