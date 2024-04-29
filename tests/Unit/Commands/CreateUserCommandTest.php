<?php

namespace Tests\Unit\Commands;

use App\Models\Project;
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
            'name' => 'default',
        ]);
    }

    public function test_create_user_and_project(): void
    {
        Project::query()->delete();
        User::query()->delete();

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
            'name' => 'default',
        ]);

        $this->assertDatabaseHas('user_project', [
            'user_id' => $user->id,
            'project_id' => $user->refresh()->current_project_id,
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
