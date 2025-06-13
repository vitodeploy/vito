<?php

namespace App\Services;

interface ServiceInterface
{
    public static function id(): string;

    public static function type(): string;

    public function unit(): string;

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function creationRules(array $input): array;

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function creationData(array $input): array;

    /**
     * @return array<string, mixed>
     */
    public function deletionRules(): array;

    /**
     * @return array<string, mixed>
     */
    public function data(): array;

    public function install(): void;

    public function uninstall(): void;
}
