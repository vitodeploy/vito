<?php

namespace App\ApplicationTypes;

use Illuminate\Contracts\View\View;

interface ApplicationType
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
    public function createFields(array $input): array;

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function data(array $input): array;

    public function install(): void;

    public function uninstall(): void;

    public function vhost(string $webserver): string|View;

    /**
     * Returns the raw Blade template source (unrendered) for editing.
     */
    public function vhostTemplate(string $webserver): string;

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function editRules(array $input): array;

    /**
     * @param  array<string, mixed>  $input
     */
    public function update(array $input): void;
}
