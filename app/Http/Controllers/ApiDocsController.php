<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Spatie\RouteAttributes\Attributes\Get;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Yaml\Yaml;

class ApiDocsController extends Controller
{
    #[Get('/api/docs', name: 'api.docs')]
    public function index(): BinaryFileResponse
    {
        return response()->file(public_path('api-docs/index.html'));
    }

    #[Get('/api.yaml', name: 'api-yaml')]
    public function apiYaml(): Response
    {
        $apiOrder = [
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
            'ssl.yaml',
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

        $combinedPaths = [];
        $combinedComponents = [
            'securitySchemes' => [],
            'schemas' => [],
            'parameters' => [],
            'responses' => [],
        ];

        $openApiDir = public_path('api-docs/openapi');

        // Load API endpoint files
        foreach ($apiOrder as $filename) {
            $filePath = $openApiDir.'/'.$filename;

            if (! file_exists($filePath)) {
                continue;
            }

            $content = file_get_contents($filePath);
            $data = Yaml::parse($content);

            if (isset($data['paths'])) {
                $combinedPaths = array_merge($combinedPaths, $data['paths']);
            }

            if (isset($data['components'])) {
                foreach (['securitySchemes', 'schemas', 'parameters', 'responses'] as $component) {
                    if (isset($data['components'][$component])) {
                        $combinedComponents[$component] = array_merge(
                            $combinedComponents[$component],
                            $data['components'][$component]
                        );
                    }
                }
            }
        }

        $schemasDir = $openApiDir.'/schemas';
        if (is_dir($schemasDir)) {
            $schemaFiles = glob($schemasDir.'/*.yaml');

            foreach ($schemaFiles as $schemaFile) {
                $content = file_get_contents($schemaFile);
                $data = Yaml::parse($content);

                if (isset($data['components']['schemas'])) {
                    $combinedComponents['schemas'] = array_merge(
                        $combinedComponents['schemas'],
                        $data['components']['schemas']
                    );
                }
            }
        }

        $combinedApi = [
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
            'paths' => $combinedPaths,
            'components' => $combinedComponents,
        ];

        $yaml = Yaml::dump($combinedApi, 10, 2);

        return response($yaml, 200, [
            'Content-Type' => 'text/yaml; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
}
