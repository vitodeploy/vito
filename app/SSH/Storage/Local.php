<?php

namespace App\SSH\Storage;

use App\SSH\HasScripts;

class Local extends AbstractStorage
{
    use HasScripts;

    public function upload(string $src, string $dest): array
    {
        $destDir = dirname($dest);
        $this->server->ssh()->exec(
            $this->getScript('local/upload.sh', [
                'src' => $src,
                'dest_dir' => $destDir,
                'dest_file' => $dest,
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
                'src' => $src,
                'dest' => $dest,
            ]),
            'download-from-local'
        );
    }

    public function delete(string $src): void
    {
        $this->server->os()->deleteFile($src);
    }
}
