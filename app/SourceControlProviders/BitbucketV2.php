<?php

namespace App\SourceControlProviders;

use App\Exceptions\FailedToDeployGitHook;
use App\Exceptions\FailedToDeployGitKey;
use App\Exceptions\FailedToDestroyGitHook;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BitbucketV2 extends AbstractSourceControlProvider
{
    protected string $apiUrl = 'https://api.bitbucket.org/2.0';

    protected string $oauthTokenUrl = 'https://bitbucket.org/site/oauth2/access_token';

    public static function id(): string
    {
        return 'bitbucket-v2';
    }

    public function createRules(array $input): array
    {
        return [
            'key' => 'required',
            'secret' => 'required',
        ];
    }

    public function createData(array $input): array
    {
        return [
            'key' => $input['key'] ?? '',
            'secret' => $input['secret'] ?? '',
        ];
    }

    public function data(): array
    {
        return [
            'key' => $this->sourceControl->provider_data['key'] ?? '',
            'secret' => $this->sourceControl->provider_data['secret'] ?? '',
            'access_token' => $this->sourceControl->provider_data['access_token'] ?? null,
        ];
    }

    /**
     * Get or refresh access token for OAuth consumer
     * Uses client credentials grant for private OAuth consumers
     */
    private function getAccessToken(): ?string
    {
        $data = $this->data();
        $accessToken = $data['access_token'] ?? null;

        // If we have an access token, return it (we'll handle expiration on API errors)
        // Bitbucket access tokens expire in 2 hours, so we'll refresh when we get 401
        if ($accessToken !== null) {
            return $accessToken;
        }

        // No token, get one using client credentials grant
        // This works for private OAuth consumers
        return $this->getAccessTokenWithClientCredentials();
    }

    /**
     * Get access token using client credentials grant
     * This works for private OAuth consumers only
     */
    private function getAccessTokenWithClientCredentials(): ?string
    {
        try {
            $data = $this->data();
            $key = $data['key'];
            $secret = $data['secret'];

            $response = Http::withBasicAuth($key, $secret)
                ->asForm()
                ->post($this->oauthTokenUrl, [
                    'grant_type' => 'client_credentials',
                ]);

            if ($response->successful()) {
                $tokenData = $response->json();
                $this->saveTokens($tokenData);

                return $tokenData['access_token'] ?? null;
            }

            $errorBody = $response->json();
            $errorDescription = $errorBody['error_description'] ?? '';

            // Check if the error is about public consumer
            if (str_contains($errorDescription, 'public') || str_contains($errorDescription, 'Cannot use client_credentials')) {
                Log::error('Bitbucket OAuth consumer is marked as public. It must be marked as private.', [
                    'status' => $response->status(),
                    'error' => $errorBody['error'] ?? 'unknown',
                    'error_description' => $errorDescription,
                    'instructions' => 'Go to Bitbucket Workspace Settings > OAuth consumers > Edit your consumer > Check "This is a private consumer" > Save',
                ]);

                throw new Exception('Your Bitbucket OAuth consumer is marked as "public" but must be marked as "private consumer" to use client credentials grant. Please edit your OAuth consumer in Bitbucket settings and check the "This is a private consumer" option.');
            }

            // Check if the error is about missing callback URL
            if (str_contains($errorDescription, 'callback') || str_contains($errorDescription, 'callback uri')) {
                Log::error('Bitbucket OAuth consumer is missing callback URL', [
                    'status' => $response->status(),
                    'error' => $errorBody['error'] ?? 'unknown',
                    'error_description' => $errorDescription,
                    'instructions' => 'Go to Bitbucket Workspace Settings > OAuth consumers > Edit your consumer > Set a Callback URL (e.g., https://your-domain.com/callback) > Save',
                ]);

                throw new Exception('Your Bitbucket OAuth consumer is missing a callback URL. Please edit your OAuth consumer in Bitbucket settings and set a Callback URL (any valid URL will work, e.g., https://example.com/callback).');
            }

            Log::error('Failed to get Bitbucket access token with client credentials', [
                'status' => $response->status(),
                'body' => $response->body(),
                'error' => $errorBody['error'] ?? 'unknown',
                'error_description' => $errorDescription,
            ]);

            throw new Exception('Failed to obtain Bitbucket access token. Error: '.$errorDescription);
        } catch (Exception $e) {
            // Re-throw configuration errors so they can be displayed to the user
            if (str_contains($e->getMessage(), 'private consumer') || str_contains($e->getMessage(), 'callback URL')) {
                throw $e;
            }

            Log::error('Error getting Bitbucket access token with client credentials', [
                'error' => $e->getMessage(),
            ]);

            throw new Exception('Failed to obtain Bitbucket access token: '.$e->getMessage());
        }
    }

    /**
     * Save access token to provider_data
     */
    private function saveTokens(array $tokenData): void
    {
        $providerData = $this->sourceControl->provider_data;
        $providerData['access_token'] = $tokenData['access_token'] ?? null;
        $this->sourceControl->provider_data = $providerData;
        $this->sourceControl->save();
    }

    /**
     * @throws Exception
     */
    public function connect(): bool
    {
        // Get access token using client credentials grant (for private OAuth consumers)
        $accessToken = $this->getAccessToken();

        if ($accessToken === null) {
            Log::error('Bitbucket V2: Failed to obtain access token', [
                'hint' => 'Make sure your OAuth consumer is marked as "private consumer" in Bitbucket settings',
            ]);

            throw new Exception('Failed to obtain Bitbucket access token. Please check your OAuth consumer configuration in Bitbucket settings.');
        }

        // Test the access token by making an API call
        $res = Http::withToken($accessToken)
            ->get($this->apiUrl.'/user');

        if ($res->successful()) {
            return true;
        }

        $errorBody = $res->json();
        $errorMessage = $errorBody['error_description'] ?? $errorBody['error'] ?? $res->body();

        Log::error('Bitbucket V2 connection failed', [
            'status' => $res->status(),
            'body' => $res->body(),
            'error' => $errorMessage,
        ]);

        // Provide user-friendly error messages based on status code
        if ($res->status() === 401) {
            throw new Exception('Bitbucket authentication failed. Please verify your Key and Secret are correct.');
        }

        if ($res->status() === 403) {
            throw new Exception('Bitbucket access denied. Please check that your OAuth consumer has the necessary permissions.');
        }

        throw new Exception('Failed to connect to Bitbucket: '.$errorMessage);
    }

    /**
     * @throws Exception
     */
    public function getRepo(string $repo): mixed
    {
        $res = Http::withHeaders($this->getAuthenticationHeaders())
            ->get($this->apiUrl."/repositories/$repo");

        $this->handleResponseErrors($res, $repo);

        return $res->json();
    }

    public function fullRepoUrl(string $repo, string $key): string
    {
        return sprintf('git@bitbucket.org-%s:%s.git', $key, $repo);
    }

    /**
     * @throws FailedToDeployGitHook
     */
    public function deployHook(string $repo, array $events, string $secret): array
    {
        try {
            $response = Http::withHeaders($this->getAuthenticationHeaders())
                ->post($this->apiUrl."/repositories/$repo/hooks", [
                    'description' => 'deploy',
                    'url' => url('/api/git-hooks?secret='.$secret),
                    'events' => [
                        'repo:'.implode(',', $events),
                    ],
                    'active' => true,
                ]);
        } catch (Exception $e) {
            throw new FailedToDeployGitHook($e->getMessage());
        }

        if ($response->status() != 201) {
            throw new FailedToDeployGitHook($response->body());
        }

        $hookData = $response->json();

        return [
            'hook_id' => $hookData['uuid'] ?? null,
            'hook_response' => $hookData,
        ];
    }

    /**
     * @throws FailedToDestroyGitHook
     */
    public function destroyHook(string $repo, string $hookId): void
    {
        $hookId = urlencode($hookId);
        try {
            $response = Http::withHeaders($this->getAuthenticationHeaders())
                ->delete($this->apiUrl."/repositories/$repo/hooks/$hookId");
        } catch (Exception $e) {
            throw new FailedToDestroyGitHook($e->getMessage());
        }

        if ($response->status() != 204) {
            throw new FailedToDestroyGitHook($response->body());
        }
    }

    /**
     * @throws Exception
     */
    public function getLastCommit(string $repo, string $branch): ?array
    {
        $res = Http::withHeaders($this->getAuthenticationHeaders())
            ->get($this->apiUrl."/repositories/$repo/commits?include=".$branch);

        $this->handleResponseErrors($res, $repo);

        $commits = $res->json();

        if (isset($commits['values']) && count($commits['values']) > 0) {
            return [
                'commit_id' => $commits['values'][0]['hash'],
                'commit_data' => [
                    'name' => $this->getCommitter($commits['values'][0]['author']['raw'])['name'] ?? null,
                    'email' => $this->getCommitter($commits['values'][0]['author']['raw'])['email'] ?? null,
                    'message' => str_replace("\n", '', $commits['values'][0]['message']),
                    'url' => $commits['values'][0]['links']['html']['href'] ?? null,
                ],
            ];
        }

        return null;
    }

    /**
     * @throws FailedToDeployGitKey
     */
    public function deployKey(string $title, string $repo, string $key): string
    {
        try {
            $res = Http::withHeaders($this->getAuthenticationHeaders())->post(
                $this->apiUrl."/repositories/$repo/deploy-keys",
                [
                    'label' => $title,
                    'key' => $key,
                ]
            );

            if ($res->status() !== 200) {
                throw new FailedToDeployGitKey($res->json()['error']['message']);
            }

            return $res->json()['id'] ?? '';
        } catch (Exception $e) {
            throw new FailedToDeployGitKey($e->getMessage());
        }
    }

    public function deleteDeployKey(string $keyId, string $repo): void
    {
        try {
            $response = Http::withHeaders($this->getAuthenticationHeaders())
                ->delete($this->apiUrl."/repositories/$repo/deploy-keys/$keyId");

            if (! $response->successful()) {
                Log::warning('Failed to delete Bitbucket deploy key', [
                    'repo' => $repo,
                    'key_id' => $keyId,
                    'response' => $response->body(),
                ]);
            }

        } catch (Exception $e) {
            Log::error('Error deleting Bitbucket deploy key', [
                'repo' => $repo,
                'key_id' => $keyId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function getCommitter(string $raw): array
    {
        $committer = explode(' <', $raw);

        return [
            'name' => $committer[0],
            'email' => Str::replace('>', '', $committer[1]),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function getAuthenticationHeaders(): array
    {
        // Get access token (will obtain one if needed using client credentials grant)
        $accessToken = $this->getAccessToken();

        if ($accessToken === null) {
            throw new Exception('Unable to obtain Bitbucket access token. Make sure your OAuth consumer is marked as "private consumer" in Bitbucket settings.');
        }

        return [
            'Authorization' => 'Bearer '.$accessToken,
        ];
    }

    public function getWebhookBranch(array $payload): string
    {
        return data_get($payload, 'push.changes.0.new.name', 'default-branch');
    }
}
