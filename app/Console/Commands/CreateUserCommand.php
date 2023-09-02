<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CreateUserCommand extends Command
{
    protected $signature = 'user:create {name} {email} {password}';

    protected $description = 'Create a new user';

    public function handle(): void
    {
        User::create([
            'name' => $this->argument('name'),
            'email' => $this->argument('email'),
            'password' => bcrypt($this->argument('password')),
        ]);

        $this->info('User created with password');
    }
}
