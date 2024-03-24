<?php

namespace Tests\Unit\Commands;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateUserCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_user(): void
    {
        $this->artisan('user:create', [
            'name' => 'John Doe',
            'email' => 'john@doe.com',
            'password' => 'password',
        ])->expectsOutput('User created!');

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@doe.com',
        ]);

        /** @var User $user */
        $user = User::query()->where('email', 'john@doe.com')->first();

        $this->assertDatabaseHas('projects', [
            'user_id' => $user->id,
        ]);
    }

    public function test_skip_existing_user(): void
    {
        $this->artisan('user:create', [
            'name' => 'John Doe',
            'email' => $this->user->email,
            'password' => 'password',
        ])->expectsOutput('User already exists. Skipping...');
    }
}
