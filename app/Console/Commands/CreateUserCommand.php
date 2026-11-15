<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CreateUserCommand extends Command
{
    protected $signature = 'user:create {name} {email} {password? : Deprecated, visible in the process list. Use --password-stdin or the prompt} {--role=admin} {--password-stdin : Read the password from standard input}';

    protected $description = 'Create a new user';

    public function handle(): int
    {
        $user = User::query()->where('email', $this->argument('email'))->first();

        if ($user) {
            $this->warn('User already exists. Skipping...');

            return self::SUCCESS;
        }

        $password = $this->password();

        if ($password === '') {
            $this->error('A password is required.');

            return self::FAILURE;
        }

        $user = User::query()->create([
            'name' => $this->argument('name'),
            'email' => $this->argument('email'),
            'password' => bcrypt($password),
            'is_admin' => $this->option('role') === 'admin',
        ]);

        $user->ensureHasDefaultProject();

        $this->info('User created!');

        return self::SUCCESS;
    }

    private function password(): string
    {
        if ($this->option('password-stdin')) {
            return rtrim((string) file_get_contents('php://stdin'), "\r\n");
        }

        if ($this->argument('password') !== null || ! $this->input->isInteractive()) {
            return (string) $this->argument('password');
        }

        return (string) $this->secret('Password');
    }
}
