<?php

namespace App\DTOs;

class VHostBlock
{
    public function __construct(
        public string $name,
        public string $location,
        public string $content,
    ) {}

    public static function make(string $name, string $location, string $content): self
    {
        return new self($name, $location, $content);
    }
}
