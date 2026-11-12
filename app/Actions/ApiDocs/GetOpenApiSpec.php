<?php

namespace App\Actions\ApiDocs;

use Symfony\Component\Yaml\Yaml;

class GetOpenApiSpec
{
    private const array FILES = [
        'base.yaml',
        'health.yaml',
        'projects.yaml',
        'servers.yaml',
        'databases.yaml',
        'sites.yaml',
        'services.yaml',
        'cronjobs.yaml',
        'workers.yaml',
        'firewall-rules.yaml',
        'server-ssl.yaml',
        'ssh-keys.yaml',
        'workflows.yaml',
        'domains.yaml',
        'dns-records.yaml',
        'dns-providers.yaml',
        'user-server-providers.yaml',
        'user-storage-providers.yaml',
        'user-source-controls.yaml',
        'server-providers.yaml',
        'storage-providers.yaml',
        'source-controls.yaml',
    ];

    private const array COMPONENTS = ['securitySchemes', 'schemas', 'parameters', 'responses'];

    /**
     * Merge the per-resource OpenAPI files into one specification.
     *
     * @return array{
     *     openapi: string,
     *     info: array<string, string>,
     *     servers: array<int, array<string, string>>,
     *     security: array<int, array<string, array<int, string>>>,
     *     paths: array<string, array<string, mixed>>,
     *     components: array<string, array<string, mixed>>,
     * }
     */
    public function handle(): array
    {
        $paths = [];
        $components = array_fill_keys(self::COMPONENTS, []);
        $openApiDir = public_path('api-docs/openapi');

        foreach (self::FILES as $filename) {
            $filePath = $openApiDir.'/'.$filename;

            if (! file_exists($filePath)) {
                continue;
            }

            $data = Yaml::parse((string) file_get_contents($filePath));

            $paths = array_merge($paths, $data['paths'] ?? []);

            foreach (self::COMPONENTS as $component) {
                $components[$component] = array_merge($components[$component], $data['components'][$component] ?? []);
            }
        }

        foreach (glob($openApiDir.'/schemas/*.yaml') ?: [] as $schemaFile) {
            $data = Yaml::parse((string) file_get_contents($schemaFile));

            $components['schemas'] = array_merge($components['schemas'], $data['components']['schemas'] ?? []);
        }

        return [
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'VitoDeploy API',
                'version' => '1.0.0',
                'description' => 'Complete API documentation for VitoDeploy - Free and Self-Hosted server management tool',
            ],
            'servers' => [
                [
                    'url' => '/',
                    'description' => 'Current Server',
                ],
            ],
            'security' => [
                ['bearerAuth' => []],
            ],
            'paths' => $paths,
            'components' => $components,
        ];
    }
}
