<?php

namespace Tests\Feature\Http;

use App\Http\Livewire\Profile\UpdatePassword;
use App\Http\Livewire\Profile\UpdateProfileInformation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $this->actingAs($this->user);

        $this
            ->get(route('profile'))
            ->assertSeeLivewire(UpdateProfileInformation::class)
            ->assertSeeLivewire(UpdatePassword::class);
    }

    public function test_profile_information_can_be_updated(): void
    {
        $this->actingAs($this->user);

        Livewire::test(UpdateProfileInformation::class)
            ->set('name', 'Test')
            ->set('email', 'test@example.com')
            ->set('timezone', 'Europe/Berlin')
            ->call('submit')
            ->assertSuccessful();

        $this->user->refresh();

        $this->assertSame('Test', $this->user->name);
        $this->assertSame('test@example.com', $this->user->email);
        $this->assertSame('Europe/Berlin', $this->user->timezone);
    }
}
