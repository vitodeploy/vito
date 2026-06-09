<?php

namespace App\Actions\GithubApp;

use App\Actions\Bootstrap\GetBootstrap;
use App\Models\GithubApp;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
            'User-Agent' => 'Vito',
        ])->send('POST', "https://api.github.com/app-manifests/{$code}/conversions");

        if (! $response->successful()) {
            Log::error('GitHub App manifest conversion failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            $message = (string) ($response->json('message') ?? '');

            throw ValidationException::withMessages([
                'github_app' => __('Failed to convert manifest (HTTP :status):status_message.', [
                    'status' => $response->status(),
                    'status_message' => $message !== '' ? ' '.$message : '',
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
