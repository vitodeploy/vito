<?php

namespace App\Actions\Script;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetScripts
{
    public function handle(User $user): LengthAwarePaginator
    {
        return $user->scripts()
            ->orderBy('id', 'desc')
            ->paginate(6)
            ->onEachSide(1);
    }
}
