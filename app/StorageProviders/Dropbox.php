<?php

namespace App\StorageProviders;

use App\Models\Server;
use App\SSHCommands\Storage\DownloadFromDropboxCommand;
use App\SSHCommands\Storage\UploadToDropboxCommand;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Throwable;

class Dropbox extends AbstractStorageProvider
{
    public function connect(): RedirectResponse
    {
        session()->put('storage_provider_id', $this->storageProvider->id);

        return Socialite::driver('dropbox')->redirect();
    }

    /**
     * @throws Throwable
     */
    public function upload(Server $server, string $src, string $dest): array
    {
        $upload = $server->ssh()->exec(
            new UploadToDropboxCommand(
                $src,
                $dest,
                $this->storageProvider->token
            ),
            'upload-to-dropbox'
        );

        $data = json_decode($upload);

        return [
            'size' => $data?->size,
        ];
    }

    /**
     * @throws Throwable
     */
    public function download(Server $server, string $src, string $dest): void
    {
        $server->ssh()->exec(
            new DownloadFromDropboxCommand(
                $src,
                $dest,
                $this->storageProvider->token
            ),
            'download-from-dropbox'
        );
    }

    public function delete(array $paths): void
    {
        $data = [];
        foreach ($paths as $path) {
            $data[] = ['path' => $path];
        }
        Http::withToken($this->storageProvider->token)
            ->withHeaders([
                'Content-Type:application/json',
            ])
            ->post('https://api.dropboxapi.com/2/files/delete_batch', [
                'entries' => $data,
            ]);
    }
}
