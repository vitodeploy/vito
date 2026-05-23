<?php

namespace App\SiteTypes;

use App\Models\Deployment;

interface SiteType
{
    public static function id(): string;

    public function language(): string;

    public function requiredServices(): array;

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

    /**
     * @return array<string, mixed>
     */
    public function vhostData(): array;

    /**
     * @return string[]|null
     */
    public function supportedWebservers(): ?array;

    public function vhostTemplate(string $webserver): ?string;

    /**
     * @return array<string, string>
     */
    public function deploymentEnvironment(): array;

    /**
     * Hook invoked after a successful deployment, before status/activation
     * is finalised. Default implementation is a no-op; site types use it to
     * lazily create or reconcile resources that depend on a built app
     * (e.g. supervisor workers).
     */
    public function afterDeploy(Deployment $deployment): void;

    /**
     * Default content for the site's `default` DeploymentScript, populated
     * by `Site::createDefaultDeploymentScript()` at site-creation time.
     */
    public function defaultDeploymentScript(): string;
}
