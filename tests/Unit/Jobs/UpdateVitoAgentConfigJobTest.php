<?php

use App\Enums\ServiceStatus;
use App\Facades\SSH;
use App\Jobs\Service\UpdateVitoAgentConfigJob;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('updates config and restarts agent', function () {
    SSH::fake();

    $agent = vitoPestUnitJobsUpdateVitoAgentConfigJobTestCreateAgent(ServiceStatus::READY);
    $this->server->services()->create([
        'type' => 'log_analysis',
        'name' => 'goaccess',
        'version' => 'latest',
        'status' => ServiceStatus::READY,
    ]);

    dispatch(new UpdateVitoAgentConfigJob($this->server));

    SSH::assertExecutedContains('/etc/vito-agent/config.json');
    SSH::assertExecutedContains("rm -f '/tmp/");
    SSH::assertExecutedContains('chmod 600 /etc/vito-agent/config.json');
    SSH::assertExecutedContains('Monitoring services: ');
    SSH::assertNotExecutedContains('agent-secret');
    SSH::assertExecutedContains('restart vito-agent');

    $config = json_decode(SSH::getUploadedContent(), true);
    expect($config['url'])->toBe('https://vito.test/agent-endpoint');
    expect($config['secret'])->toBe('agent-secret');
    expect($config)->not->toHaveKey('data_retention');

    $units = array_column($config['services'], 'unit', 'id');
    expect($units)->toHaveCount(6);
    expect($units)->toContain('nginx');
    expect($units)->toContain('mysql');
    expect($units)->toContain('php8.2-fpm');
    expect($units)->toContain('ufw');
    expect($units)->toContain('supervisor');
    expect($units)->toContain('redis-server');
    expect($units)->not->toContain('');
    expect($units)->not->toHaveKey($agent->id);
});

test('does nothing without vito agent', function () {
    SSH::fake();

    dispatch(new UpdateVitoAgentConfigJob($this->server));

    SSH::assertNotExecutedContains('vito-agent');
    expect(SSH::getUploadedContent())->toBe('');
});

test('does nothing when agent is not ready', function () {
    SSH::fake();

    vitoPestUnitJobsUpdateVitoAgentConfigJobTestCreateAgent(ServiceStatus::STOPPED);

    dispatch(new UpdateVitoAgentConfigJob($this->server));

    SSH::assertNotExecutedContains('restart vito-agent');
    expect(SSH::getUploadedContent())->toBe('');
});

test('dispatch for never throws into the calling job', function () {
    Queue::fake();

    vitoPestUnitJobsUpdateVitoAgentConfigJobTestCreateAgent(ServiceStatus::READY);
    config()->set('service.services.broken.handler', 'App\Services\DoesNotExist');

    /** @var Service $broken */
    $broken = $this->server->services()->create([
        'type' => 'broken',
        'name' => 'broken',
        'version' => 'latest',
        'status' => ServiceStatus::READY,
    ]);

    UpdateVitoAgentConfigJob::dispatchFor($broken);

    Queue::assertNotPushed(UpdateVitoAgentConfigJob::class);
});

function vitoPestUnitJobsUpdateVitoAgentConfigJobTestCreateAgent(ServiceStatus $status): Service
{
    /** @var Service $service */
    $service = Service::factory()->vitoAgent()->create([
        'server_id' => test()->server->id,
        'status' => $status,
    ]);

    return $service;
}
