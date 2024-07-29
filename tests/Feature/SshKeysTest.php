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

        $this->post(route('settings.ssh-keys.add'), [
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

        $this->get(route('settings.ssh-keys'))
            ->assertSuccessful()
            ->assertSee($key->name);
    }

    public function test_delete_key(): void
    {
        $this->actingAs($this->user);

        $key = SshKey::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->delete(route('settings.ssh-keys.delete', $key->id))
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseMissing('ssh_keys', [
            'id' => $key->id,
        ]);
    }

    /**
     * @dataProvider ssh_key_data_provider
     */
    public function test_create_ssh_key_handles_invalid_or_partial_keys(array $postBody, bool $expectedToSucceed): void
    {
        $this->actingAs($this->user);

        // Some existing amount of seed data to make the test more realistic
        SshKey::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'My first key',
            'public_key' => 'public-key-content',
        ]);

        $response = $this->post(route('settings.ssh-keys.add'), $postBody);

        if ($expectedToSucceed) {
            $response->assertSessionDoesntHaveErrors();
        } else {
            $response->assertSessionHasErrors('public_key', 'Invalid key');
        }
    }

    public static function ssh_key_data_provider(): array
    {
        return [
            [
                [
                    'name' => 'My first key',
                    // Key Already exists
                    'public_key' => 'public-key-content',
                ],
                self::EXPECT_FAILURE,
            ],
            [
                [
                    'name' => 'My first key',
                    // RSA type
                    'public_key' => 'ssh-rsa AAAAB3NzaC1yc2EAAAABIwAAAQEAklOUpkDHrfHY17SbrmTIpNLTGK9Tjom/BWDSUGPl+nafzlHDTYW7hdI4yZ5ew18JH4JW9jbhUFrviQzM7xlELEVf4h9lFX5QVkbPppSwg0cda3Pbv7kOdJ/MTyBlWXFCR+HAo3FXRitBqxiX1nKhXpHAZsMciLq8V6RjsNAQwdsdMFvSlVK/7XAt3FaoJoAsncM1Q9x5+3V0Ww68/eIFmb1zuUFljQJKprrX88XypNDvjYNby6vw/Pb0rwert/EnmZ+AW4OZPnTPI89ZPmVMLuayrD2cE86Z/il8b+gw3r3+1nKatmIkjn2so1d01QraTlMqVSsbxNrRFi9wrf+M7Q== test@test.local',
                ],
                self::EXPECT_SUCCESS,
            ],
            [
                [
                    'name' => 'My first key',
                    //  DSS
                    'public_key' => 'ssh-dss AAAAB3NzaC1kc3MAAACBAPLQ1v111G0vuWwurCJdg0rt2C8OwWW88sR3sCS+CPjvqPyRtFOiJLqnO0/J/tIlCZVHN0VWgKN19jOiMy2Kx2mMZoAJ0z6AGxQMoTB0eqBeYYfAfxL9KwVw6EV7QWXTu5/EDbl+6k60EQ9EXOIsnhTQsok2Ki52Td0TUxfA3Vy7AAAAFQDx8Fi0pWPoJDn7jUqog7L748iBowAAAIADrgFxfpQuujYgxS2RXX8euxgrfa1KMQNT2kXQ3781L7ihS5EjyFy03K2pV0DSQo2NeFSJv9rtJNXfCDoAofUhgugZkMWUAv8ebZsy6SxWAocjw6A5ED1YkvA03eNUEaSm9vb8ts8m1wJme6Urx41Yh9c2YVXB6yJ7T1jaeZrhbQAAAIEA3hp8vZeImWol/tTm4LE1FXkqU7cMo863MAs5gdqRhJ5Xnwvsbx1WSVajJY78e8s/Yd4GhBAJpNjAVfep0CqbfJeNKV/D6oCKCfVikKefRw4GIREtAfkijlh/WOkwmWE0VcZRQk1sIizYtqIwtghvMvzDRSGFMC3l6hF5AHYyrSg= test@test.local',
                ],
                self::EXPECT_SUCCESS,
            ],
            [
                [
                    'name' => 'My first key',
                    // ECDSA type
                    'public_key' => 'ecdsa-sha2-nistp256 AAAAE2VjZHNhLXNoYTItbmlzdHAyNTYAAAAIbmlzdHAyNTYAAABBBBY0xeD9/iTVZZO/qVFRjAwtNmC0zHVWqY4Q7nmB4nGvfpHhlze+rEpxXwNWW5olIcAjAXJO+gQCa4iNV6UYDp8= test@test.local',
                ],
                self::EXPECT_SUCCESS,
            ],
            [
                [
                    'name' => 'My first key',
                    // ED25519 type
                    'public_key' => 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIIxUhYjgZEXKYnlerxHYyL/e0HZ4C9t3WCpQ/LCPQZMp test@test.local',
                ],
                self::EXPECT_SUCCESS,
            ],
            [
                [
                    'name' => 'My first key',
                    // Partial ED25519 type
                    'public_key' => 'ssh-afeef AAAAC3NzaC1lZDI1NTE5AAAAIIxUhYjgZEXKYnlerxHYyL/e0HZ4C9t3WCpQ/LCPQZMp test@test.local',
                ],
                self::EXPECT_FAILURE,
            ],
            [
                [
                    'name' => 'My first key',
                    // Partial ED25519 type
                    'public_key' => 'ssh-ed25519 AAAAC3NzAffefIIIAAAAAAAIIxUhYjgZEXKYnlerxHYyL/e0HZ4C9t3WCpQ/LCPQZMp test@test.local',
                ],
                self::EXPECT_FAILURE,
            ],
            [
                [
                    'name' => 'My first key',
                    // Partial DSS type, close but not quite
                    'public_key' => 'ssh-dss AAAAB3NzaC1kc3MAAACBAPLQ1v111G0vuWwurCJdga830asW88sR3sCS+CPjvqPyRtFOiJLqnO0/J/tIlCZVHN0VWgKN19jOiMy2Kx2mMZoAJ0z6AGxQMoTB0eqBeYYfAfxL9KwVw6EV7QWXTu5/EDbl+6k60EQ9EXOIsnhTQsok2Ki52Td0TUxfA3Vy7AAAAFQDx8Fi0pWPoJDn7jUqog7L748iBowAAAIADrgFxfpQuujYgxS2RXX8euxgrfa1KMQNT2kXQ3781L7ihS5EjyFy03K2pV0DSQo2NeFSJv9rtJNXfCDoAofUhgugZkMWUAv8ebZsy6SxWAocjw6A5ED1YkvA03eNUEaSm9vb8ts8m1wJme6Urx41Yh9c2YVXB6yJ7T1jaeZrhbQAAAIEA3hp8vZeImWol/tTm4LE1FXkqU7cMo863MAs5gdqRhJ5Xnwvsbx1WSVajJYa103s/Yd4GhBAJpNjAVfep0CqbfJeNKV/D6oCKCfVikKefRw4GIREtAfkijlh/WOkwmWE0VcZRQk1sIizYtqIwtghvMvzDRSGFMC3l6hF5AHYyrSg= test@test.local',
                ],
                self::EXPECT_FAILURE,
            ],
        ];
    }
}
