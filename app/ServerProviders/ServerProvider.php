<?php

namespace App\ServerProviders;

interface ServerProvider
{
    public static function id(): string;

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function createRules(array $input): array;

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function credentialValidationRules(array $input): array;

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function credentialData(array $input): array;

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function data(array $input): array;

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function connect(array $credentials): bool;

    /**
     * @return array<string, mixed>
     */
    public function plans(?string $region): array;

    /**
     * @return array<string, mixed>
     */
    public function regions(): array;

    public function generateKeyPair(): void;

    public function create(): void;

    public function isRunning(): bool;

    public function delete(): void;
}
