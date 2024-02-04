<?php

namespace Tests\Feature\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServerModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_have_default_service()
    {
        $php = $this->server->defaultService('php');
        $php->update(['is_default' => false]);
        $this->assertNotNull($this->server->defaultService('php'));
        $php->refresh();
        $this->assertTrue($php->is_default);
    }
}
