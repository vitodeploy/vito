<?php

namespace App\DTOs;

final readonly class VerificationResult
{
    public function __construct(
        public bool $verified,
        public ?string $failureReason,
    ) {}

    public static function success(): self
    {
        return new self(true, null);
    }

    public static function failure(string $reason): self
    {
        return new self(false, $reason);
    }
}
