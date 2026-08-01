<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('api docs endpoint returns html documentation', function () {
    $response = $this->get('/api/docs');

    $response->assertSuccessful();
});

test('api yaml endpoint returns valid yaml specification', function () {
    $response = $this->get('/api.yaml');

    $response->assertSuccessful();
    $response->assertHeader('content-type', 'text/yaml; charset=UTF-8');
    $response->assertHeader('cache-control', 'must-revalidate, no-cache, no-store, private');
    $response->assertHeader('pragma', 'no-cache');
    $response->assertHeader('expires', '0');

    $yamlContent = $response->getContent();
    $this->assertStringContainsString('openapi: 3.0.0', $yamlContent);
    $this->assertStringContainsString("title: 'VitoDeploy API'", $yamlContent);
    $this->assertStringContainsString('version: 1.0.0', $yamlContent);
    $this->assertStringContainsString("description: 'Complete API documentation for VitoDeploy - Free and Self-Hosted server management tool'", $yamlContent);
});
