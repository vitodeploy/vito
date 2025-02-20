<?php

namespace App\Cli\Commands;

use App\Models\User;
use Illuminate\Console\Command;

use function Laravel\Prompts\error;

abstract class AbstractCommand extends Command
{
    private User|null $user = null;

    public function user()
    {
        if ($this->user) {
            return $this->user->refresh();
        }

        /** @var User $user */
        $user = User::query()->first();

        if (!$user) {
            error('The application is not setup');
            exit(1);
        }

        $this->user = $user;

        return $user;
    }
}
