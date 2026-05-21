<?php

namespace App\Actions\GithubApp;

use App\Actions\Bootstrap\GetBootstrap;
use App\Models\GithubApp;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class CreateGithubAppFromManifest
{
    public function create(string $code): GithubApp
    {
        if (GithubApp::query()->exists()) {
            throw ValidationException::withMessages([
                'github_app' => __('A GitHub App is already configured for this instance.'),
            ]);
        }

        $response = Http::withHeaders([
            'Accept' => 'application/vnd.github+json',
            'X-GitHub-Api-Version' => '2022-11-28',
        ])->post("https://api.github.com/app-manifests/{$code}/conversions");

        if (! $response->successful()) {
            throw ValidationException::withMessages([
                'github_app' => __('Failed to convert manifest (HTTP :status).', [
                    'status' => $response->status(),
                ]),
            ]);
        }

        $data = $response->json();

        $app = new GithubApp([
            'app_id' => (int) ($data['id'] ?? 0),
            'app_slug' => (string) ($data['slug'] ?? ''),
            'name' => (string) ($data['name'] ?? ''),
            'client_id' => (string) ($data['client_id'] ?? ''),
            'client_secret' => (string) ($data['client_secret'] ?? ''),
            'webhook_secret' => (string) ($data['webhook_secret'] ?? ''),
            'private_key' => (string) ($data['pem'] ?? ''),
            'html_url' => $data['html_url'] ?? null,
        ]);

        $app->save();

        GetBootstrap::forgetVersion();

        return $app;
    }
}
