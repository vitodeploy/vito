<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordConfirmationTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirm_password_screen_can_be_rendered(): void
    {
        $this->actingAs($this->user);

        $response = $this->get(route('password.confirm'));

        $response->assertStatus(200);
    }

    public function test_password_can_be_confirmed(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('password.confirm'), [
            'password' => 'password',
        ]);

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors();
    }

    public function test_password_is_not_confirmed_with_invalid_password(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('password.confirm'), [
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors();
    }
}
