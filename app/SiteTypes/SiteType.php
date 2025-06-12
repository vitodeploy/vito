<?php

namespace App\SiteTypes;

interface SiteType
{
    public static function id(): string;

    public function language(): string;

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function createRules(array $input): array;

    /**
     * The fields here will be replaced in the Site model
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function createFields(array $input): array;

    /**
     * The fields here will be replaced in the type_data column as json
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function data(array $input): array;

    public function install(): void;

    /**
     * @return array<array<string, string>>
     */
    public function baseCommands(): array;

    public function vhost(string $webserver): string;
}
