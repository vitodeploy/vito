<?php

namespace Tests\Feature;

use App\Web\Pages\Settings\Profile\Index;
use App\Web\Pages\Settings\Profile\Widgets\ProfileInformation;
use App\Web\Pages\Settings\Profile\Widgets\UpdatePassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $this->actingAs($this->user);

        $this
            ->get(Index::getUrl())
            ->assertSuccessful()
            ->assertSee('Profile Information')
            ->assertSee('Update Password')
            ->assertSee('Two Factor Authentication');
    }

    public function test_profile_information_can_be_updated(): void
    {
        $this->actingAs($this->user);

        Livewire::test(ProfileInformation::class)
            ->fill([
                'name' => 'Test',
                'email' => 'test@example.com',
                'timezone' => 'Europe/Berlin',
            ])
            ->call('submit');

        $this->user->refresh();

        $this->assertSame('Test', $this->user->name);
        $this->assertSame('test@example.com', $this->user->email);
        $this->assertSame('Europe/Berlin', $this->user->timezone);
    }

    public function test_password_can_be_updated(): void
    {
        $this->actingAs($this->user);

        Livewire::test(UpdatePassword::class)
            ->fill([
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->call('submit');

        $this->assertTrue(Hash::check('new-password', $this->user->refresh()->password));
    }

    public function test_correct_password_must_be_provided_to_update_password(): void
    {
        $this->actingAs($this->user);

        Livewire::test(UpdatePassword::class)
            ->fill([
                'current_password' => 'wrong-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->call('submit')
            ->assertHasErrors('current_password');
    }
}
