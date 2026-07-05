<?php

namespace Tests\Unit;

use App\Support\DesktopRuntime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DesktopRuntimeTest extends TestCase
{
    use RefreshDatabase;

    public function test_builds_desktop_websocket_url_with_allocated_port(): void
    {
        config()->set('app.url', 'http://127.0.0.1:47291');
        config()->set('app.ws_url', null);
        config()->set('core.ws_port', 48125);
        config()->set('desktop.enabled', true);

        $this->assertSame('ws://127.0.0.1:48125/ws/events', DesktopRuntime::websocketUrl('/ws/events'));
    }

    public function test_builds_server_websocket_url_without_desktop_port(): void
    {
        config()->set('app.url', 'https://vito.example.com');
        config()->set('app.ws_url', null);
        config()->set('core.ws_port', 48125);
        config()->set('desktop.enabled', false);

        $this->assertSame('wss://vito.example.com/ws/events', DesktopRuntime::websocketUrl('/ws/events'));
    }

    public function test_joins_desktop_storage_paths(): void
    {
        config()->set('desktop.storage_path', '/tmp/vito/storage');

        $this->assertSame('/tmp/vito/storage/framework/views', DesktopRuntime::storagePath('framework/views'));
    }
}
