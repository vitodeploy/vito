<?php

namespace App\DTOs\GitHub;

final readonly class AuthorDto
{
    public function __construct(
        public string $login,
        public string $url,
        public string $type,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            login: $data['login'],
            url: $data['url'],
            type: $data['type'],
        );
    }
}
