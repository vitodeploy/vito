<?php

namespace App\SourceControlProviders;

use App\Exceptions\FailedToDeployGitKey;
use App\Exceptions\RepositoryNotFound;
use App\Exceptions\RepositoryPermissionDenied;
use App\Exceptions\SourceControlIsNotConnected;
use App\Models\SourceControl;
use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Throwable;

abstract class AbstractSourceControlProvider implements SourceControlProvider
{
    public function __construct(protected SourceControl $sourceControl) {}

    public function createRules(array $input): array
    {
        return [
            'token' => 'required',
        ];
    }

    public function repositoryRules(): array
    {
        return ['regex:~\A[a-zA-Z0-9_.-]+/[a-zA-Z0-9_.-]+\z~'];
    }

    public function createData(array $input): array
    {
        return [
            'token' => $input['token'] ?? '',
        ];
    }

    public function data(): array
    {
        // support for older data
        $token = $this->sourceControl->access_token ?? '';

        return [
            'token' => $this->sourceControl->provider_data['token'] ?? $token,
        ];
    }

    /**
     * @throws SourceControlIsNotConnected
     * @throws RepositoryNotFound
     * @throws RepositoryPermissionDenied
     * @throws RequestException
     */
    protected function handleResponseErrors(Response $res, string $repo): void
    {
        if ($res->status() == 401) {
            throw new SourceControlIsNotConnected($this->sourceControl);
        }

        if ($res->status() == 404) {
            throw new RepositoryNotFound($repo);
        }

        if ($res->status() == 403) {
            throw new RepositoryPermissionDenied($repo);
        }

        $res->throw();
    }

    /**
     * @param  Closure(): PendingRequest  $client
     * @param  array{key: string, title?: string, label?: string, read_only?: bool, can_push?: bool}  $payload
     *
     * @throws FailedToDeployGitKey
     */
    protected function registerDeployKey(Closure $client, string $url, array $payload, string $pageSizeParameter = 'per_page', ?string $collectionKey = null): string
    {
        try {
            try {
                $response = $client()->post($url, $payload);
            } catch (ConnectionException) {
                $response = null;
            }
            $id = $response?->json('id');
            if ($response?->successful() && (is_int($id) || is_string($id)) && (string) $id !== '') {
                return (string) $id;
            }

            return $this->lookupDeployKey($client, $url, $payload['key'], $pageSizeParameter, $collectionKey)
                ?? throw new FailedToDeployGitKey('Unable to register the source-control deploy key.');
        } catch (Throwable) {
            throw new FailedToDeployGitKey('Unable to register the source-control deploy key.');
        }
    }

    /**
     * @param  Closure(): PendingRequest  $client
     *
     * @throws FailedToDeployGitKey
     */
    protected function lookupDeployKey(Closure $client, string $url, string $publicKey, string $pageSizeParameter = 'per_page', ?string $collectionKey = null): ?string
    {
        try {
            $request = $client();
            $key = array_slice(preg_split('/\s+/', trim($publicKey)) ?: [], 0, 2);
            if (count($key) !== 2) {
                throw new FailedToDeployGitKey('Invalid deploy public key.');
            }
            for ($page = 1; $page <= 25; $page++) {
                $response = $request->get($url, ['page' => $page, $pageSizeParameter => 100])->throw();
                $keys = $response->json($collectionKey);
                if (! is_array($keys) || ! array_is_list($keys)) {
                    throw new FailedToDeployGitKey('Invalid deploy-key response.');
                }
                foreach ($keys as $existing) {
                    $candidate = array_slice(preg_split('/\s+/', trim($existing['key'] ?? '')) ?: [], 0, 2);
                    $id = $existing['id'] ?? null;
                    if (count($candidate) !== 2 || (! is_int($id) && ! is_string($id)) || (string) $id === '') {
                        throw new FailedToDeployGitKey('Invalid deploy-key response.');
                    }
                    if ($candidate === $key) {
                        return (string) $id;
                    }
                }
                if ($keys === []) {
                    return null;
                }
            }
        } catch (Throwable) {
            throw new FailedToDeployGitKey('Unable to look up the source-control deploy key.');
        }

        throw new FailedToDeployGitKey('Unable to look up the source-control deploy key.');
    }

    public function getWebhookBranch(array $payload): string
    {
        return str($payload['ref'] ?? '')->after('refs/heads/')->toString();
    }

    public function getPushBranches(array $payload): array
    {
        $ref = $payload['ref'] ?? '';
        if (! is_string($ref) || ! str_starts_with($ref, 'refs/heads/') || ($payload['deleted'] ?? false)
            || ($payload['after'] ?? null) === str_repeat('0', 40)) {
            return [];
        }

        return [substr($ref, 11)];
    }

    public function getSshPort(): int
    {
        return 22;
    }

    /**
     * @return array<int, string>
     */
    public static function editableFields(): array
    {
        return [];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, array<int, string>>
     */
    public function editRules(array $input): array
    {
        return [];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     *
     * @note Subclasses that return a non-empty editableFields() MUST override
     *       this method to whitelist exactly which keys are merged from $input.
     *       Spreading $input here would let an attacker overwrite encrypted
     *       fields like `token`.
     */
    public function editData(array $input): array
    {
        return $this->sourceControl->provider_data ?? [];
    }

    /**
     * @return array<int, string>
     */
    protected function sshPortRules(): array
    {
        return ['sometimes', 'nullable', 'integer', 'min:1', 'max:65535'];
    }

    protected function normalizeSshPort(mixed $value): int
    {
        if ($value === null || $value === '') {
            return 22;
        }

        return (int) $value;
    }

    public function getRepos(bool $useCache = true): array
    {
        return [];
    }

    public function getBranches(string $repo, bool $useCache = true): array
    {
        return [];
    }
}
