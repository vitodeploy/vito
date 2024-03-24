<?php

namespace App\StorageProviders;

use App\Models\Server;
use App\SSH\Storage\Storage;
use Illuminate\Support\Facades\Http;

class Dropbox extends AbstractStorageProvider
{
    protected string $apiUrl = 'https://api.dropboxapi.com/2';

    public function validationRules(): array
    {
        return [
            'token' => 'required',
        ];
    }

    public function credentialData(array $input): array
    {
        return [
            'token' => $input['token'],
        ];
    }

    public function connect(): bool
    {
        $res = Http::withToken($this->storageProvider->credentials['token'])
            ->post($this->apiUrl.'/check/user', [
                'query' => '',
            ]);

        return $res->successful();
    }

    public function ssh(Server $server): Storage
    {
        return new \App\SSH\Storage\Dropbox($server, $this->storageProvider);
    }

    public function delete(array $paths): void
    {
        $data = [];
        foreach ($paths as $path) {
            $data[] = ['path' => $path];
        }
        Http::withToken($this->storageProvider->credentials['token'])
            ->withHeaders([
                'Content-Type:application/json',
            ])
            ->post($this->apiUrl.'/files/delete_batch', [
                'entries' => $data,
            ]);
    }
}
