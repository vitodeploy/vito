<?php

namespace App\Actions\GithubApp;

use App\Actions\Bootstrap\GetBootstrap;
use App\Models\GithubApp;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CreateGithubAppManually
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function create(array $input): GithubApp
    {
        if (GithubApp::query()->exists()) {
            throw ValidationException::withMessages([
                'github_app' => __('A GitHub App is already configured for this instance.'),
            ]);
        }

        Validator::make($input, [
            'app_id' => ['required', 'integer'],
            'name' => ['nullable', 'string', 'max:255'],
            'client_id' => ['required', 'string', 'max:255'],
            'client_secret' => ['required', 'string'],
            'webhook_secret' => ['required', 'string'],
            'private_key' => ['required', 'string'],
            'html_url' => ['required', 'url', 'max:500'],
        ])->validate();

        $htmlUrl = rtrim((string) $input['html_url'], '/');
        $slug = $this->extractSlug($htmlUrl);
        if ($slug === null) {
            throw ValidationException::withMessages([
                'html_url' => __('The app page URL must look like https://github.com/apps/<slug>.'),
            ]);
        }

        $privateKey = trim((string) $input['private_key']);
        if (! str_contains($privateKey, 'BEGIN') || ! str_contains($privateKey, 'PRIVATE KEY')) {
            throw ValidationException::withMessages([
                'private_key' => __('The private key must be a PEM-formatted RSA private key.'),
            ]);
        }

        $resource = openssl_pkey_get_private($privateKey);
        if ($resource === false) {
            throw ValidationException::withMessages([
                'private_key' => __('The private key could not be parsed.'),
            ]);
        }

        $app = new GithubApp([
            'app_id' => (int) $input['app_id'],
            'app_slug' => $slug,
            'name' => (string) ($input['name'] ?? $slug),
            'client_id' => (string) $input['client_id'],
            'client_secret' => (string) $input['client_secret'],
            'webhook_secret' => (string) $input['webhook_secret'],
            'private_key' => $privateKey,
            'html_url' => $htmlUrl,
        ]);

        $app->save();

        GetBootstrap::forgetVersion();

        return $app;
    }

    private function extractSlug(string $htmlUrl): ?string
    {
        if (! preg_match('#^https://github\.com/apps/([A-Za-z0-9._-]+)/?$#', $htmlUrl, $m)) {
            return null;
        }

        return $m[1];
    }
}
