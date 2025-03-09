<?php

namespace App\ServerTypes;

interface ServerType
{
    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function createRules(array $input): array;

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function data(array $input): array;

    /**
     * @param  array<string, mixed>  $input
     */
    public function createServices(array $input): void;

    public function install(): void;
}
