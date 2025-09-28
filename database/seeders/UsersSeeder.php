<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'name' => 'Demo User',
            'email' => 'demo@vitodeploy.com',
        ]);

        $user->ensureHasDefaultProject();
    }
}
