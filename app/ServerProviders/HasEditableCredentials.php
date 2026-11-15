<?php

namespace App\ServerProviders;

interface HasEditableCredentials extends ServerProvider
{
    /**
     * @return array<string, mixed>
     */
    public function editableData(): array;

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function editRules(array $input): array;

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function editCredentials(array $input): array;
}
