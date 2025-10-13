<?php

namespace Tests\Unit\Models;

use App\DTOs\WorkflowActionDTO;
use App\Models\Workflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_execution_tree_returns_null_when_no_nodes(): void
    {
        $workflow = Workflow::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
            'payload' => [
                'nodes' => [],
                'edges' => [],
            ],
        ]);

        $result = $workflow->getExecutionTree();

        $this->assertNull($result);
    }

    public function test_get_execution_tree_returns_null_when_no_starting_node(): void
    {
        $workflow = Workflow::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
            'payload' => [
                'nodes' => [
                    [
                        'id' => 'node-1',
                        'data' => [
                            'action' => [
                                'label' => 'Test Action',
                                'handler' => 'TestHandler',
                                'outputs' => [],
                                'inputs' => [],
                                'starting' => false,
                            ],
                        ],
                    ],
                ],
                'edges' => [],
            ],
        ]);

        $result = $workflow->getExecutionTree();

        $this->assertNull($result);
    }

    public function test_get_execution_tree_returns_single_node_dto(): void
    {
        $workflow = Workflow::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
            'payload' => [
                'nodes' => [
                    [
                        'id' => 'node-1',
                        'data' => [
                            'action' => [
                                'label' => 'Create Server',
                                'handler' => 'App\\WorkflowActions\\Server\\CreateServer',
                                'outputs' => [
                                    'server_id' => 'The ID of the created server',
                                    'server_ip' => 'The IP address of the created server',
                                ],
                                'inputs' => [
                                    'name' => 'test-server',
                                    'provider' => 'digitalocean',
                                ],
                                'starting' => true,
                            ],
                        ],
                    ],
                ],
                'edges' => [],
            ],
        ]);

        $result = $workflow->getExecutionTree();

        $this->assertInstanceOf(WorkflowActionDTO::class, $result);
        $this->assertEquals('Create Server', $result->label);
        $this->assertEquals('App\\WorkflowActions\\Server\\CreateServer', $result->handler);
        $this->assertEquals(['server_id', 'server_ip'], $result->outputs);
        $this->assertEquals(['name' => 'test-server', 'provider' => 'digitalocean'], $result->inputs);
        $this->assertEquals('node-1', $result->id);
        $this->assertNull($result->success);
        $this->assertNull($result->failure);
    }

    public function test_get_execution_tree_handles_success_and_failure_branches(): void
    {
        $workflow = Workflow::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
            'payload' => [
                'nodes' => [
                    [
                        'id' => 'node-1',
                        'data' => [
                            'action' => [
                                'label' => 'Create Server',
                                'handler' => 'App\\WorkflowActions\\Server\\CreateServer',
                                'outputs' => ['server_id'],
                                'inputs' => [],
                                'starting' => true,
                            ],
                        ],
                    ],
                    [
                        'id' => 'node-2',
                        'data' => [
                            'action' => [
                                'label' => 'Install Service',
                                'handler' => 'App\\WorkflowActions\\Service\\InstallService',
                                'outputs' => ['service_id'],
                                'inputs' => [],
                                'starting' => false,
                            ],
                        ],
                    ],
                    [
                        'id' => 'node-3',
                        'data' => [
                            'action' => [
                                'label' => 'Create Site',
                                'handler' => 'App\\WorkflowActions\\Site\\CreateSite',
                                'outputs' => ['site_id'],
                                'inputs' => [],
                                'starting' => false,
                            ],
                        ],
                    ],
                ],
                'edges' => [
                    [
                        'source' => 'node-1',
                        'target' => 'node-2',
                        'data' => ['status' => 'success'],
                    ],
                    [
                        'source' => 'node-1',
                        'target' => 'node-3',
                        'data' => ['status' => 'failure'],
                    ],
                ],
            ],
        ]);

        $result = $workflow->getExecutionTree();

        $this->assertInstanceOf(WorkflowActionDTO::class, $result);
        $this->assertEquals('Create Server', $result->label);
        $this->assertEquals('node-1', $result->id);

        // Check success branch
        $this->assertInstanceOf(WorkflowActionDTO::class, $result->success);
        $this->assertEquals('Install Service', $result->success->label);
        $this->assertEquals('node-2', $result->success->id);
        $this->assertNull($result->success->success);
        $this->assertNull($result->success->failure);

        // Check failure branch
        $this->assertInstanceOf(WorkflowActionDTO::class, $result->failure);
        $this->assertEquals('Create Site', $result->failure->label);
        $this->assertEquals('node-3', $result->failure->id);
        $this->assertNull($result->failure->success);
        $this->assertNull($result->failure->failure);
    }

    public function test_get_execution_tree_handles_deep_nested_workflows(): void
    {
        $workflow = Workflow::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
            'payload' => [
                'nodes' => [
                    [
                        'id' => 'node-1',
                        'data' => [
                            'action' => [
                                'label' => 'Start',
                                'handler' => 'StartHandler',
                                'outputs' => ['start_id'],
                                'inputs' => [],
                                'starting' => true,
                            ],
                        ],
                    ],
                    [
                        'id' => 'node-2',
                        'data' => [
                            'action' => [
                                'label' => 'Step 1',
                                'handler' => 'Step1Handler',
                                'outputs' => ['step1_id'],
                                'inputs' => [],
                                'starting' => false,
                            ],
                        ],
                    ],
                    [
                        'id' => 'node-3',
                        'data' => [
                            'action' => [
                                'label' => 'Step 2',
                                'handler' => 'Step2Handler',
                                'outputs' => ['step2_id'],
                                'inputs' => [],
                                'starting' => false,
                            ],
                        ],
                    ],
                    [
                        'id' => 'node-4',
                        'data' => [
                            'action' => [
                                'label' => 'Final',
                                'handler' => 'FinalHandler',
                                'outputs' => ['final_id'],
                                'inputs' => [],
                                'starting' => false,
                            ],
                        ],
                    ],
                ],
                'edges' => [
                    [
                        'source' => 'node-1',
                        'target' => 'node-2',
                        'data' => ['status' => 'success'],
                    ],
                    [
                        'source' => 'node-2',
                        'target' => 'node-3',
                        'data' => ['status' => 'success'],
                    ],
                    [
                        'source' => 'node-3',
                        'target' => 'node-4',
                        'data' => ['status' => 'success'],
                    ],
                ],
            ],
        ]);

        $result = $workflow->getExecutionTree();

        $this->assertInstanceOf(WorkflowActionDTO::class, $result);
        $this->assertEquals('Start', $result->label);

        // Check nested success chain
        $this->assertInstanceOf(WorkflowActionDTO::class, $result->success);
        $this->assertEquals('Step 1', $result->success->label);

        $this->assertInstanceOf(WorkflowActionDTO::class, $result->success->success);
        $this->assertEquals('Step 2', $result->success->success->label);

        $this->assertInstanceOf(WorkflowActionDTO::class, $result->success->success->success);
        $this->assertEquals('Final', $result->success->success->success->label);
        $this->assertNull($result->success->success->success->success);
    }

    public function test_get_execution_tree_handles_string_payload(): void
    {
        $payload = [
            'nodes' => [
                [
                    'id' => 'node-1',
                    'data' => [
                        'action' => [
                            'label' => 'Test Action',
                            'handler' => 'TestHandler',
                            'outputs' => ['test_id'],
                            'inputs' => [],
                            'starting' => true,
                        ],
                    ],
                ],
            ],
            'edges' => [],
        ];

        $workflow = Workflow::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
            'payload' => $payload,
        ]);

        $result = $workflow->getExecutionTree();

        $this->assertInstanceOf(WorkflowActionDTO::class, $result);
        $this->assertEquals('Test Action', $result->label);
        $this->assertEquals('node-1', $result->id);
    }

    public function test_get_execution_tree_handles_missing_action_data(): void
    {
        $workflow = Workflow::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
            'payload' => [
                'nodes' => [
                    [
                        'id' => 'node-1',
                        'data' => [
                            'action' => [
                                'starting' => true,
                            ],
                        ],
                    ],
                ],
                'edges' => [],
            ],
        ]);

        $result = $workflow->getExecutionTree();

        $this->assertInstanceOf(WorkflowActionDTO::class, $result);
        $this->assertEquals('', $result->label);
        $this->assertEquals('', $result->handler);
        $this->assertEquals([], $result->outputs);
        $this->assertEquals([], $result->inputs);
        $this->assertEquals('node-1', $result->id);
    }

    public function test_get_execution_tree_handles_missing_edge_status(): void
    {
        $workflow = Workflow::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
            'payload' => [
                'nodes' => [
                    [
                        'id' => 'node-1',
                        'data' => [
                            'action' => [
                                'label' => 'Start',
                                'handler' => 'StartHandler',
                                'outputs' => [],
                                'inputs' => [],
                                'starting' => true,
                            ],
                        ],
                    ],
                    [
                        'id' => 'node-2',
                        'data' => [
                            'action' => [
                                'label' => 'Next',
                                'handler' => 'NextHandler',
                                'outputs' => [],
                                'inputs' => [],
                                'starting' => false,
                            ],
                        ],
                    ],
                ],
                'edges' => [
                    [
                        'source' => 'node-1',
                        'target' => 'node-2',
                        'data' => [], // Missing status
                    ],
                ],
            ],
        ]);

        $result = $workflow->getExecutionTree();

        $this->assertInstanceOf(WorkflowActionDTO::class, $result);
        $this->assertEquals('Start', $result->label);
        // Should default to success when status is missing
        $this->assertInstanceOf(WorkflowActionDTO::class, $result->success);
        $this->assertEquals('Next', $result->success->label);
    }

    public function test_get_execution_tree_to_array_method(): void
    {
        $workflow = Workflow::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
            'payload' => [
                'nodes' => [
                    [
                        'id' => 'node-1',
                        'data' => [
                            'action' => [
                                'label' => 'Create Server',
                                'handler' => 'App\\WorkflowActions\\Server\\CreateServer',
                                'outputs' => [
                                    'server_id' => 'The ID of the created server',
                                    'server_ip' => 'The IP address of the created server',
                                ],
                                'inputs' => [
                                    'name' => 'test-server',
                                ],
                                'starting' => true,
                            ],
                        ],
                    ],
                    [
                        'id' => 'node-2',
                        'data' => [
                            'action' => [
                                'label' => 'Install Service',
                                'handler' => 'App\\WorkflowActions\\Service\\InstallService',
                                'outputs' => ['service_id'],
                                'inputs' => [],
                                'starting' => false,
                            ],
                        ],
                    ],
                ],
                'edges' => [
                    [
                        'source' => 'node-1',
                        'target' => 'node-2',
                        'data' => ['status' => 'success'],
                    ],
                ],
            ],
        ]);

        $result = $workflow->getExecutionTree();
        $array = $result->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('run', $array);
        $this->assertArrayHasKey('success', $array);
        $this->assertArrayHasKey('failure', $array);

        $this->assertEquals('Create Server', $array['run']['label']);
        $this->assertEquals('App\\WorkflowActions\\Server\\CreateServer', $array['run']['handler']);
        $this->assertEquals(['name' => 'test-server'], $array['run']['inputs']);
        $this->assertEquals(['server_id', 'server_ip'], $array['run']['outputs']);
        $this->assertEquals('node-1', $array['run']['id']);

        $this->assertIsArray($array['success']);
        $this->assertEquals('Install Service', $array['success']['run']['label']);
        $this->assertNull($array['failure']);
    }
}
