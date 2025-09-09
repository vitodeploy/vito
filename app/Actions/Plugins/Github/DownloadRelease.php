<?php

namespace App\Actions\Plugins\Github;

use App\DTOs\GitHub\ReleaseDto;
use Exception;
use Illuminate\Support\Facades\File;

class DownloadRelease
{
    public function __construct() {}

    /**
     * @throws Exception
     */
    public function handle(ReleaseDto $release, string $location): void
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: PHP',
                ],
                'timeout' => 30,
            ],
        ]);

        if (! File::isDirectory(dirname($location))) {
            File::makeDirectory(path: dirname($location), recursive: true);
        }

        $content = @file_get_contents($release->zipUrl, false, $context);
        if ($content === false) {
            throw new Exception('Unable to download zip file');
        }

        file_put_contents($location, $content);
    }
}
