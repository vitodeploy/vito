<?php

namespace App\Actions\Site;

use App\Exceptions\SSHError;
use App\Models\Site;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

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
            'path' => ['nullable', 'string', 'regex:/^[a-zA-Z0-9\/_.\-]+$/'],
        ])->validate();

        $typeData = $site->type_data ?? [];
        $path = $input['path'] ?? data_get($typeData, 'env_path', $site->path.'/.env');

        $storedEnvPath = data_get($typeData, 'env_path');
        $withinSitePath = str_starts_with($path, $site->path.'/');
        $matchesStoredPath = $storedEnvPath !== null && $path === $storedEnvPath;

        if (str_contains($path, '..') || (! $withinSitePath && ! $matchesStoredPath)) {
            throw ValidationException::withMessages([
                'path' => __('The path must be within the site directory.'),
            ]);
        }

        $site->server->os()->write(
            $path,
            trim((string) $input['env']),
            $site->user,
        );

        $site->jsonUpdate('type_data', 'env_path', $path);
    }
}
