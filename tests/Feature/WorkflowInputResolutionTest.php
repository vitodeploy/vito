<?php

namespace Tests\Feature;

use App\Actions\Workflow\RunWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowInputResolutionTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolve_inputs_replaces_placeholders_with_previous_outputs(): void
    {
        $runWorkflow = new RunWorkflow;

        // Use reflection to access the private resolveInputs method
        $reflection = new \ReflectionClass($runWorkflow);
        $method = $reflection->getMethod('resolveInputs');
        $method->setAccessible(true);

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

        $this->assertEquals([
            'server_id' => 123,
            'server_ip' => '192.168.1.100',
            'server_status' => 'active',
            'command' => 'echo "Hello from server 123"', // String interpolation now handled
            'user' => 'root',
            'custom_value' => 'static_value',
        ], $result);
    }

    public function test_resolve_inputs_handles_missing_placeholders(): void
    {
        $runWorkflow = new RunWorkflow;

        $reflection = new \ReflectionClass($runWorkflow);
        $method = $reflection->getMethod('resolveInputs');
        $method->setAccessible(true);

        $previousOutputs = [
            'server_id' => 123,
        ];

        $actionInputs = [
            'server_id' => '{server_id}',
            'missing_key' => '{missing_key}',
            'regular_value' => 'test',
        ];

        $result = $method->invoke($runWorkflow, $previousOutputs, $actionInputs);

        $this->assertEquals([
            'server_id' => 123,
            'missing_key' => '{missing_key}', // Kept as placeholder since not found in previous outputs
            'regular_value' => 'test',
        ], $result);
    }

    public function test_resolve_inputs_handles_non_string_values(): void
    {
        $runWorkflow = new RunWorkflow;

        $reflection = new \ReflectionClass($runWorkflow);
        $method = $reflection->getMethod('resolveInputs');
        $method->setAccessible(true);

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

        $this->assertEquals([
            'server_id' => 123,
            'numeric_value' => 456,
            'array_value' => ['key' => 'value'],
            'boolean_value' => true,
        ], $result);
    }

    public function test_resolve_inputs_merges_previous_outputs_with_action_inputs(): void
    {
        $runWorkflow = new RunWorkflow;

        $reflection = new \ReflectionClass($runWorkflow);
        $method = $reflection->getMethod('resolveInputs');
        $method->setAccessible(true);

        $previousOutputs = [
            'server_id' => 123,
            'service_id' => 456,
        ];

        $actionInputs = [
            'server_id' => '{server_id}',
            'command' => 'install package',
        ];

        $result = $method->invoke($runWorkflow, $previousOutputs, $actionInputs);

        // Should include all previous outputs plus resolved action inputs
        $this->assertEquals([
            'server_id' => 123,
            'service_id' => 456,
            'command' => 'install package',
        ], $result);
    }

    public function test_resolve_inputs_handles_string_interpolation(): void
    {
        $runWorkflow = new RunWorkflow;

        $reflection = new \ReflectionClass($runWorkflow);
        $method = $reflection->getMethod('resolveInputs');
        $method->setAccessible(true);

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

        $this->assertEquals([
            'server_id' => 123,
            'server_ip' => '192.168.1.100',
            'service_name' => 'nginx',
            'command' => 'echo "Server 123 is running at 192.168.1.100"',
            'log_message' => 'Installing nginx on server 123',
            'status_check' => 'curl -f http://192.168.1.100/health',
        ], $result);
    }

    public function test_resolve_inputs_handles_mixed_placeholders_and_interpolation(): void
    {
        $runWorkflow = new RunWorkflow;

        $reflection = new \ReflectionClass($runWorkflow);
        $method = $reflection->getMethod('resolveInputs');
        $method->setAccessible(true);

        $previousOutputs = [
            'server_id' => 123,
            'server_ip' => '192.168.1.100',
        ];

        $actionInputs = [
            'server_id' => '{server_id}', // Exact placeholder
            'command' => 'echo "Server {server_id} at {server_ip}"', // String interpolation
            'missing_placeholder' => 'echo {missing_key}', // Missing placeholder
        ];

        $result = $method->invoke($runWorkflow, $previousOutputs, $actionInputs);

        $this->assertEquals([
            'server_id' => 123,
            'server_ip' => '192.168.1.100',
            'command' => 'echo "Server 123 at 192.168.1.100"',
            'missing_placeholder' => 'echo {missing_key}', // Kept as-is since missing_key not found
        ], $result);
    }

    public function test_resolve_inputs_handles_double_curly_braces(): void
    {
        $runWorkflow = new RunWorkflow;

        $reflection = new \ReflectionClass($runWorkflow);
        $method = $reflection->getMethod('resolveInputs');
        $method->setAccessible(true);

        $previousOutputs = [
            'service_id' => 456,
            'server_id' => 123,
        ];

        $actionInputs = [
            'service_id' => '{{service_id}}', // Exact double placeholder
            'command' => 'echo "${{service_id}} installed"', // String interpolation with double braces
            'server_id' => '{{server_id}}', // Exact double placeholder
        ];

        $result = $method->invoke($runWorkflow, $previousOutputs, $actionInputs);

        $this->assertEquals([
            'service_id' => 456,
            'server_id' => 123,
            'command' => 'echo "$456 installed"',
        ], $result);
    }

    public function test_resolve_inputs_prioritizes_previous_outputs_over_action_inputs(): void
    {
        $runWorkflow = new RunWorkflow;

        $reflection = new \ReflectionClass($runWorkflow);
        $method = $reflection->getMethod('resolveInputs');
        $method->setAccessible(true);

        $previousOutputs = [
            'server_id' => 123, // This should take priority
            'server_ip' => '192.168.1.100',
        ];

        $actionInputs = [
            'server_id' => 999, // This should be overridden
            'command' => 'echo "Using server {server_id}"',
        ];

        $result = $method->invoke($runWorkflow, $previousOutputs, $actionInputs);

        $this->assertEquals([
            'server_id' => 999,
            'server_ip' => '192.168.1.100',
            'command' => 'echo "Using server 123"',
        ], $result);
    }
}
