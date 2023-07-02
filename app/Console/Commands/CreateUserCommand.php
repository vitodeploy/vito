<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreateUserCommand extends Command
{
    protected $signature = 'user:create {name} {email}';

    protected $description = 'Create a new user';

    public function handle(): void
    {
        $password = Str::random(20);
        User::create([
            'name' => $this->argument('name'),
            'email' => $this->argument('email'),
            'password' => bcrypt($password),
        ]);

        $this->info("User created with password: {$password}");
    }
}
