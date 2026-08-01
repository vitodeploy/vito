<?php

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('create user', function () {
    $this->artisan('user:create', [
        'name' => 'John Doe',
        'email' => 'john@doe.com',
        'password' => 'password',
    ])->expectsOutput('User created!');

    $this->assertDatabaseHas('users', [
        'name' => 'John Doe',
        'email' => 'john@doe.com',
    ]);

    $user = User::query()->where('email', 'john@doe.com')->firstOrFail();

    $this->assertDatabaseHas('projects', [
        'name' => 'default',
    ]);

    $this->assertDatabaseHas('user_project', [
        'user_id' => $user->id,
        'project_id' => $user->refresh()->current_project_id,
    ]);
});

test('create user and project', function () {
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

    $user = User::query()->where('email', 'john@doe.com')->firstOrFail();

    $this->assertDatabaseHas('projects', [
        'name' => 'default',
    ]);

    $this->assertDatabaseHas('user_project', [
        'user_id' => $user->id,
        'project_id' => $user->refresh()->current_project_id,
    ]);
});

test('skip existing user', function () {
    $this->artisan('user:create', [
        'name' => 'John Doe',
        'email' => $this->user->email,
        'password' => 'password',
    ])->expectsOutput('User already exists. Skipping...');
});
