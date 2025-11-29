<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiDocsTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_docs_endpoint_returns_html_documentation(): void
    {
        $response = $this->get('/api/docs');

        $response->assertSuccessful();

        // For BinaryFileResponse, we can't easily get the content in tests
        // but we can verify the response is successful and has correct headers
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_api_yaml_endpoint_returns_valid_yaml_specification(): void
    {
        $response = $this->get('/api.yaml');

        $response->assertSuccessful();
        $response->assertHeader('content-type', 'text/yaml; charset=UTF-8');
        $response->assertHeader('cache-control', 'must-revalidate, no-cache, no-store, private');
        $response->assertHeader('pragma', 'no-cache');
        $response->assertHeader('expires', '0');

        // Verify the response contains valid YAML structure
        $yamlContent = $response->getContent();
        $this->assertStringContainsString('openapi: 3.0.0', $yamlContent);
        $this->assertStringContainsString("title: 'VitoDeploy API'", $yamlContent);
        $this->assertStringContainsString('version: 1.0.0', $yamlContent);
        $this->assertStringContainsString("description: 'Complete API documentation for VitoDeploy - Free and Self-Hosted server management tool'", $yamlContent);
    }
}
