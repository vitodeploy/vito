<?php

namespace App\ServerProviders;

/**
 * Opt-in capability. Providers implementing this let users change some of a
 * connection's credentials after connecting, through the edit form registered
 * with `RegisterServerProvider::editForm()`.
 */
interface HasEditableCredentials extends ServerProvider
{
    /**
     * Non-secret credential values the edit form is prefilled with.
     *
     * @return array<string, mixed>
     */
    public function editableData(): array;

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function editRules(array $input): array;

    /**
     * The stored credentials with the edit form's changes merged in.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function editCredentials(array $input): array;
}
