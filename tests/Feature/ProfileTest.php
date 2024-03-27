<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $this->actingAs($this->user);

        $this
            ->get(route('profile'))
            ->assertSuccessful()
            ->assertSee('Profile Information')
            ->assertSee('Update Password')
            ->assertSee('Two Factor Authentication');
    }

    public function test_profile_information_can_be_updated(): void
    {
        $this->actingAs($this->user);

        $this->post(route('profile.info'), [
            'name' => 'Test',
            'email' => 'test@example.com',
            'timezone' => 'Europe/Berlin',
        ]);

        $this->user->refresh();

        $this->assertSame('Test', $this->user->name);
        $this->assertSame('test@example.com', $this->user->email);
        $this->assertSame('Europe/Berlin', $this->user->timezone);
    }

    public function test_password_can_be_updated(): void
    {
        $this->actingAs($this->user);

        $this->post(route('profile.password'), [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $this->assertTrue(Hash::check('new-password', $this->user->refresh()->password));
    }

    public function test_correct_password_must_be_provided_to_update_password(): void
    {
        $this->actingAs($this->user);

        $this->post(route('profile.password'), [
            'current_password' => 'wrong-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertSessionHasErrors('current_password');
    }
}
