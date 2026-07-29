<?php

namespace App\Services\Database;

use App\Services\ManagesNetworking;

trait ManagesDatabaseNetworking
{
    use ManagesNetworking;

    /**
     * @return array<string, mixed>
     */
    protected function networkingExtraDetails(): array
    {
        return [
            'requires_remote_users' => $this->usesHost(),
        ];
    }

    protected function getNetworkingScriptView(string $script): string
    {
        return $this->getScriptView($script);
    }

    abstract public function usesHost(): bool;

    abstract protected function getScriptView(string $script): string;

    protected function networkingValueMatches(string $output, string ...$expected): bool
    {
        foreach ($expected as $value) {
            if (preg_match('/^\s*'.preg_quote($value, '/').'\s*$/m', $output) === 1) {
                return true;
            }
        }

        return false;
    }
}
