<?php

namespace App\DTOs;

final readonly class VerificationResult
{
    /**
     * @param  array<int, string>  $resolvedIps
     */
    public function __construct(
        public bool $verified,
        public ?string $failureReason,
        public array $resolvedIps,
    ) {}

    /**
     * @param  array<int, string>  $resolvedIps
     */
    public static function success(array $resolvedIps): self
    {
        return new self(true, null, $resolvedIps);
    }

    /**
     * @param  array<int, string>  $resolvedIps
     */
    public static function failure(string $reason, array $resolvedIps = []): self
    {
        return new self(false, $reason, $resolvedIps);
    }
}
