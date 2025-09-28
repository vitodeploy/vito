<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CreateUserCommand extends Command
{
    protected $signature = 'user:create {name} {email} {password} {--role=admin}';

    protected $description = 'Create a new user';

    public function handle(): void
    {
        $user = User::query()->where('email', $this->argument('email'))->first();

        if ($user) {
            $this->warn('User already exists. Skipping...');

            return;
        }

        $user = User::query()->create([
            'name' => $this->argument('name'),
            'email' => $this->argument('email'),
            'password' => bcrypt($this->argument('password')),
            'is_admin' => $this->option('role') === 'admin',
        ]);

        $user->ensureHasDefaultProject();

        $this->info('User created!');
    }
}
