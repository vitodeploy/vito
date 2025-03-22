<?php

namespace App\SiteTypes;

interface SiteType
{
    public function language(): string;

    /**
     * @return array<string>
     */
    public function supportedFeatures(): array;

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function createRules(array $input): array;

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function createFields(array $input): array;

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function data(array $input): array;

    public function install(): void;

    public function duplicateSite(): void;

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function editRules(array $input): array;

    public function edit(): void;

    /**
     * @return array<array<string, string>>
     */
    public function baseCommands(): array;
}
