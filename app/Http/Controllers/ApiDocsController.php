<?php

namespace App\Http\Controllers;

use App\Actions\ApiDocs\GetOpenApiSpec;
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
        $yaml = Yaml::dump(app(GetOpenApiSpec::class)->handle(), 10, 2);

        return response($yaml, 200, [
            'Content-Type' => 'text/yaml; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
}
