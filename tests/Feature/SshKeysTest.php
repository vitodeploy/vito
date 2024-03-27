<?php

namespace Tests\Feature;

use App\Models\SshKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SshKeysTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_ssh_key(): void
    {
        $this->actingAs($this->user);

        $this->post(route('ssh-keys.add'), [
            'name' => 'test',
            'public_key' => 'ssh-rsa AAAAB3NzaC1yc2EAAAABIwAAAQEAklOUpkDHrfHY17SbrmTIpNLTGK9Tjom/BWDSUGPl+nafzlHDTYW7hdI4yZ5ew18JH4JW9jbhUFrviQzM7xlELEVf4h9lFX5QVkbPppSwg0cda3Pbv7kOdJ/MTyBlWXFCR+HAo3FXRitBqxiX1nKhXpHAZsMciLq8V6RjsNAQwdsdMFvSlVK/7XAt3FaoJoAsncM1Q9x5+3V0Ww68/eIFmb1zuUFljQJKprrX88XypNDvjYNby6vw/Pb0rwert/EnmZ+AW4OZPnTPI89ZPmVMLuayrD2cE86Z/il8b+gw3r3+1nKatmIkjn2so1d01QraTlMqVSsbxNrRFi9wrf+M7Q== test@test.local',
        ])->assertSessionDoesntHaveErrors();
    }

    public function test_get_public_keys_list(): void
    {
        $this->actingAs($this->user);

        $key = SshKey::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->get(route('ssh-keys'))
            ->assertSuccessful()
            ->assertSee($key->name);
    }

    public function test_delete_key(): void
    {
        $this->actingAs($this->user);

        $key = SshKey::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->delete(route('ssh-keys.delete', $key->id))
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseMissing('ssh_keys', [
            'id' => $key->id,
        ]);
    }
}
