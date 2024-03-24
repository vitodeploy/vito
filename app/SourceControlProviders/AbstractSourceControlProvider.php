<?php

namespace App\SourceControlProviders;

use App\Exceptions\RepositoryNotFound;
use App\Exceptions\RepositoryPermissionDenied;
use App\Exceptions\SourceControlIsNotConnected;
use App\Models\SourceControl;
use Illuminate\Http\Client\Response;

abstract class AbstractSourceControlProvider implements SourceControlProvider
{
    protected SourceControl $sourceControl;

    public function __construct(SourceControl $sourceControl)
    {
        $this->sourceControl = $sourceControl;
    }

    public function createRules(array $input): array
    {
        return [
            'token' => 'required',
            'url' => [
                'nullable',
                'url:http,https',
                'ends_with:/',
            ],
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
}
