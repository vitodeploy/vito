<?php

use App\Actions\Workflow\RunWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('resolve inputs replaces placeholders with previous outputs', function () {
    $runWorkflow = new RunWorkflow;

    $reflection = new ReflectionClass($runWorkflow);
    $method = $reflection->getMethod('resolveInputs');

    $previousOutputs = [
        'server_id' => 123,
        'server_ip' => '192.168.1.100',
        'server_status' => 'active',
    ];

    $actionInputs = [
        'server_id' => '{server_id}',
        'command' => 'echo "Hello from server {server_id}"',
        'user' => 'root',
        'custom_value' => 'static_value',
    ];

    $result = $method->invoke($runWorkflow, $previousOutputs, $actionInputs);

    expect($result)->toEqual([
        'server_id' => 123,
        'server_ip' => '192.168.1.100',
        'server_status' => 'active',
        'command' => 'echo "Hello from server 123"',
        'user' => 'root',
        'custom_value' => 'static_value',
    ]);
});

test('resolve inputs handles missing placeholders', function () {
    $runWorkflow = new RunWorkflow;

    $reflection = new ReflectionClass($runWorkflow);
    $method = $reflection->getMethod('resolveInputs');

    $previousOutputs = [
        'server_id' => 123,
    ];

    $actionInputs = [
        'server_id' => '{server_id}',
        'missing_key' => '{missing_key}',
        'regular_value' => 'test',
    ];

    $result = $method->invoke($runWorkflow, $previousOutputs, $actionInputs);

    expect($result)->toEqual([
        'server_id' => 123,
        'missing_key' => '{missing_key}',
        'regular_value' => 'test',
    ]);
});

test('resolve inputs handles non string values', function () {
    $runWorkflow = new RunWorkflow;

    $reflection = new ReflectionClass($runWorkflow);
    $method = $reflection->getMethod('resolveInputs');

    $previousOutputs = [
        'server_id' => 123,
    ];

    $actionInputs = [
        'server_id' => '{server_id}',
        'numeric_value' => 456,
        'array_value' => ['key' => 'value'],
        'boolean_value' => true,
    ];

    $result = $method->invoke($runWorkflow, $previousOutputs, $actionInputs);

    expect($result)->toEqual([
        'server_id' => 123,
        'numeric_value' => 456,
        'array_value' => ['key' => 'value'],
        'boolean_value' => true,
    ]);
});

test('resolve inputs merges previous outputs with action inputs', function () {
    $runWorkflow = new RunWorkflow;

    $reflection = new ReflectionClass($runWorkflow);
    $method = $reflection->getMethod('resolveInputs');

    $previousOutputs = [
        'server_id' => 123,
        'service_id' => 456,
    ];

    $actionInputs = [
        'server_id' => '{server_id}',
        'command' => 'install package',
    ];

    $result = $method->invoke($runWorkflow, $previousOutputs, $actionInputs);

    expect($result)->toEqual([
        'server_id' => 123,
        'service_id' => 456,
        'command' => 'install package',
    ]);
});

test('resolve inputs handles string interpolation', function () {
    $runWorkflow = new RunWorkflow;

    $reflection = new ReflectionClass($runWorkflow);
    $method = $reflection->getMethod('resolveInputs');

    $previousOutputs = [
        'server_id' => 123,
        'server_ip' => '192.168.1.100',
        'service_name' => 'nginx',
    ];

    $actionInputs = [
        'command' => 'echo "Server {server_id} is running at {server_ip}"',
        'log_message' => 'Installing {service_name} on server {server_id}',
        'status_check' => 'curl -f http://{server_ip}/health',
    ];

    $result = $method->invoke($runWorkflow, $previousOutputs, $actionInputs);

    expect($result)->toEqual([
        'server_id' => 123,
        'server_ip' => '192.168.1.100',
        'service_name' => 'nginx',
        'command' => 'echo "Server 123 is running at 192.168.1.100"',
        'log_message' => 'Installing nginx on server 123',
        'status_check' => 'curl -f http://192.168.1.100/health',
    ]);
});

test('resolve inputs handles mixed placeholders and interpolation', function () {
    $runWorkflow = new RunWorkflow;

    $reflection = new ReflectionClass($runWorkflow);
    $method = $reflection->getMethod('resolveInputs');

    $previousOutputs = [
        'server_id' => 123,
        'server_ip' => '192.168.1.100',
    ];

    $actionInputs = [
        'server_id' => '{server_id}',
        'command' => 'echo "Server {server_id} at {server_ip}"',
        'missing_placeholder' => 'echo {missing_key}',
    ];

    $result = $method->invoke($runWorkflow, $previousOutputs, $actionInputs);

    expect($result)->toEqual([
        'server_id' => 123,
        'server_ip' => '192.168.1.100',
        'command' => 'echo "Server 123 at 192.168.1.100"',
        'missing_placeholder' => 'echo {missing_key}',
    ]);
});

test('resolve inputs handles double curly braces', function () {
    $runWorkflow = new RunWorkflow;

    $reflection = new ReflectionClass($runWorkflow);
    $method = $reflection->getMethod('resolveInputs');

    $previousOutputs = [
        'service_id' => 456,
        'server_id' => 123,
    ];

    $actionInputs = [
        'service_id' => '{{service_id}}',
        'command' => 'echo "${{service_id}} installed"',
        'server_id' => '{{server_id}}',
    ];

    $result = $method->invoke($runWorkflow, $previousOutputs, $actionInputs);

    expect($result)->toEqual([
        'service_id' => 456,
        'server_id' => 123,
        'command' => 'echo "$456 installed"',
    ]);
});

test('resolve inputs keeps action values while interpolating previous outputs', function () {
    $runWorkflow = new RunWorkflow;

    $reflection = new ReflectionClass($runWorkflow);
    $method = $reflection->getMethod('resolveInputs');

    $previousOutputs = [
        'server_id' => 123,
        'server_ip' => '192.168.1.100',
    ];

    $actionInputs = [
        'server_id' => 999,
        'command' => 'echo "Using server {server_id}"',
    ];

    $result = $method->invoke($runWorkflow, $previousOutputs, $actionInputs);

    expect($result)->toEqual([
        'server_id' => 999,
        'server_ip' => '192.168.1.100',
        'command' => 'echo "Using server 123"',
    ]);
});
