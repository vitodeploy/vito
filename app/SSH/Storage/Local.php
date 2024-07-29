<?php

namespace App\SSH\Storage;

use App\SSH\HasScripts;

class Local extends AbstractStorage
{
    use HasScripts;

    public function upload(string $src, string $dest): array
    {
        $destDir = dirname($this->storageProvider->credentials['path'].$dest);
        $destFile = basename($this->storageProvider->credentials['path'].$dest);
        $this->server->ssh()->exec(
            $this->getScript('local/upload.sh', [
                'src' => $src,
                'dest_dir' => $destDir,
                'dest_file' => $destFile,
            ]),
            'upload-to-local'
        );

        return [
            'size' => null,
        ];
    }

    public function download(string $src, string $dest): void
    {
        $this->server->ssh()->exec(
            $this->getScript('local/download.sh', [
                'src' => $this->storageProvider->credentials['path'].$src,
                'dest' => $dest,
            ]),
            'download-from-local'
        );
    }

    public function delete(string $path): void
    {
        $this->server->ssh()->exec(
            $this->getScript('local/delete.sh', [
                'path' => $this->storageProvider->credentials['path'].$path,
            ]),
            'delete-from-local'
        );
    }
}
