<?php

namespace App\Actions\Plugins\Github;

use App\DTOs\GitHub\ReleaseDto;

class GetReleaseInfo
{
    public function __construct() {}

    public function handle(string $username, string $repo, string $version = 'latest'): ?ReleaseDto
    {
        $url = "https://api.github.com/repos/$username/$repo/releases/";
        $url .= $version === 'latest' ? 'latest' : 'tags/'.$version;

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: PHP',
                ],
                'timeout' => 30,
            ],
        ]);

        $content = @file_get_contents($url, false, $context);
        if ($content === false) {
            return null;
        }

        return ReleaseDto::fromGitHub($content);
    }
}
