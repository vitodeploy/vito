<?php

namespace Tests\Feature\Http\Auth;

use App\Http\Livewire\Profile\UpdatePassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class PasswordUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_can_be_updated(): void
    {
        $this->actingAs($this->user);

        Livewire::test(UpdatePassword::class)
            ->set('current_password', 'password')
            ->set('password', 'new-password')
            ->set('password_confirmation', 'new-password')
            ->call('update')
            ->assertSuccessful();

        $this->assertTrue(Hash::check('new-password', $this->user->refresh()->password));
    }

    public function test_correct_password_must_be_provided_to_update_password(): void
    {
        $this->actingAs($this->user);

        Livewire::test(UpdatePassword::class)
            ->set('current_password', 'wrong-password')
            ->set('password', 'new-password')
            ->set('password_confirmation', 'new-password')
            ->call('update')
            ->assertHasErrors();
    }
}
