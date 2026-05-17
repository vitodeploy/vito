<?php

namespace App\SourceControlProviders;

use App\Exceptions\RepositoryNotFound;
use App\Exceptions\RepositoryPermissionDenied;
use App\Exceptions\SourceControlIsNotConnected;
use App\Models\SourceControl;
use Illuminate\Http\Client\Response;

abstract class AbstractSourceControlProvider implements SourceControlProvider
{
    public function __construct(protected SourceControl $sourceControl) {}

    public function createRules(array $input): array
    {
        return [
            'token' => 'required',
        ];
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
    }

    public function getWebhookBranch(array $payload): string
    {
        return str($payload['ref'] ?? '')->after('refs/heads/')->toString();
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
     */
    public function editData(array $input): array
    {
        return $this->sourceControl->provider_data ?? [];
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
