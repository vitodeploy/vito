<?php

namespace App\Actions\Site;

use App\Exceptions\SSHError;
use App\Models\Site;
use Illuminate\Support\Facades\Validator;

class UpdateEnv
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws SSHError
     */
    public function update(Site $site, array $input): void
    {
        Validator::make($input, [
            'env' => ['required', 'string'],
            'path' => ['nullable', 'string'],
        ])->validate();

        $typeData = $site->type_data ?? [];
        $path = $input['path'] ?? data_get($typeData, 'env_path', $site->path.'/.env');

        $site->server->os()->write(
            $path,
            trim((string) $input['env']),
            $site->user,
        );

        $site->jsonUpdate('type_data', 'env_path', $path);
    }
}
