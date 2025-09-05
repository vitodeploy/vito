<?php

namespace App\DTOs\GitHub;

use Carbon\Carbon;

final readonly class ReleaseDto
{
    public function __construct(
        public string $url,
        public string $tagName,
        public string $name,
        public bool $draft,
        public bool $preRelease,
        public Carbon $createdAt,
        public Carbon $updatedAt,
        public Carbon $publishedAt,
        public AuthorDto $author,
        public string $tarUrl,
        public string $zipUrl,
        public string $body
    ) {}

    public static function fromGitHub(string $json): self
    {
        $data = json_decode($json, true);

        return new self(
            url: $data['url'],
            tagName: $data['tag_name'],
            name: $data['name'],
            draft: $data['draft'],
            preRelease: $data['prerelease'],
            createdAt: Carbon::parse($data['created_at']),
            updatedAt: Carbon::parse($data['updated_at']),
            publishedAt: Carbon::parse($data['published_at']),
            author: AuthorDto::fromArray($data['author']),
            tarUrl: $data['tarball_url'],
            zipUrl: $data['zipball_url'],
            body: $data['body']
        );
    }
}
