<?php

namespace App\Actions\Site;

use App\Helpers\EnvParser;
use App\Models\Site;

class GetEnv
{
    /**
     * Read the live .env file and return it alongside its variables classified
     * against the persisted secret keys, with secret values masked for display.
     *
     * @return array{env: string, variables: array<int, array{key: string, value: string, is_secret: bool}>}
     */
    public function get(Site $site): array
    {
        $env = $site->getEnv();

        $variables = EnvParser::maskSecrets(
            EnvParser::classify(EnvParser::parse($env), $site->env_variables)
        );

        return [
            'env' => $env,
            'variables' => $variables,
        ];
    }
}
