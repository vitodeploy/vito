<?php

use App\DTOs\WorkflowActionDTO;
use App\Models\Workflow;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('get execution tree returns null when no nodes', function () {
    $workflow = Workflow::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->user->current_project_id,
        'payload' => [
            'nodes' => [],
            'edges' => [],
        ],
    ]);

    $result = $workflow->getExecutionTree();

    expect($result)->toBeNull();
});

test('get execution tree returns null when no starting node', function () {
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

    expect($result)->toBeNull();
});

test('get execution tree returns single node dto', function () {
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

    expect($result)->toBeInstanceOf(WorkflowActionDTO::class);
    expect($result->label)->toEqual('Create Server');
    expect($result->handler)->toEqual('App\\WorkflowActions\\Server\\CreateServer');
    expect($result->outputs)->toEqual(['server_id', 'server_ip']);
    expect($result->inputs)->toEqual(['name' => 'test-server', 'provider' => 'digitalocean']);
    expect($result->id)->toEqual('node-1');
    expect($result->success)->toBeNull();
    expect($result->failure)->toBeNull();
});

test('get execution tree handles success and failure branches', function () {
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

    expect($result)->toBeInstanceOf(WorkflowActionDTO::class);
    expect($result->label)->toEqual('Create Server');
    expect($result->id)->toEqual('node-1');

    // Check success branch
    expect($result->success)->toBeInstanceOf(WorkflowActionDTO::class);
    expect($result->success->label)->toEqual('Install Service');
    expect($result->success->id)->toEqual('node-2');
    expect($result->success->success)->toBeNull();
    expect($result->success->failure)->toBeNull();

    // Check failure branch
    expect($result->failure)->toBeInstanceOf(WorkflowActionDTO::class);
    expect($result->failure->label)->toEqual('Create Site');
    expect($result->failure->id)->toEqual('node-3');
    expect($result->failure->success)->toBeNull();
    expect($result->failure->failure)->toBeNull();
});

test('get execution tree handles deep nested workflows', function () {
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

    expect($result)->toBeInstanceOf(WorkflowActionDTO::class);
    expect($result->label)->toEqual('Start');

    // Check nested success chain
    expect($result->success)->toBeInstanceOf(WorkflowActionDTO::class);
    expect($result->success->label)->toEqual('Step 1');

    expect($result->success->success)->toBeInstanceOf(WorkflowActionDTO::class);
    expect($result->success->success->label)->toEqual('Step 2');

    expect($result->success->success->success)->toBeInstanceOf(WorkflowActionDTO::class);
    expect($result->success->success->success->label)->toEqual('Final');
    expect($result->success->success->success->success)->toBeNull();
});

test('get execution tree handles string payload', function () {
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

    expect($result)->toBeInstanceOf(WorkflowActionDTO::class);
    expect($result->label)->toEqual('Test Action');
    expect($result->id)->toEqual('node-1');
});

test('get execution tree handles missing action data', function () {
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

    expect($result)->toBeInstanceOf(WorkflowActionDTO::class);
    expect($result->label)->toEqual('');
    expect($result->handler)->toEqual('');
    expect($result->outputs)->toEqual([]);
    expect($result->inputs)->toEqual([]);
    expect($result->id)->toEqual('node-1');
});

test('get execution tree handles missing edge status', function () {
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

    expect($result)->toBeInstanceOf(WorkflowActionDTO::class);
    expect($result->label)->toEqual('Start');

    // Should default to success when status is missing
    expect($result->success)->toBeInstanceOf(WorkflowActionDTO::class);
    expect($result->success->label)->toEqual('Next');
});

test('get execution tree to array method', function () {
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

    expect($array)->toBeArray();
    expect($array)->toHaveKey('run');
    expect($array)->toHaveKey('success');
    expect($array)->toHaveKey('failure');

    expect($array['run']['label'])->toEqual('Create Server');
    expect($array['run']['handler'])->toEqual('App\\WorkflowActions\\Server\\CreateServer');
    expect($array['run']['inputs'])->toEqual(['name' => 'test-server']);
    expect($array['run']['outputs'])->toEqual(['server_id', 'server_ip']);
    expect($array['run']['id'])->toEqual('node-1');

    expect($array['success'])->toBeArray();
    expect($array['success']['run']['label'])->toEqual('Install Service');
    expect($array['failure'])->toBeNull();
});
