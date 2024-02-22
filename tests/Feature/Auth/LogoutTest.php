<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_logout(): void
    {
        $this->actingAs($this->user);

        $this->post(route('logout'))->assertRedirect('/');

        $this->assertFalse(auth()->check());
    }

    public function test_user_still_logged_in(): void
    {
        $this->actingAs($this->user);

        $this->get(route('login'))->assertRedirect(route('servers'));

        $this->assertTrue(auth()->check());
    }
}
