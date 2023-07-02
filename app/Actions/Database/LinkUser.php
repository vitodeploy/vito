<?php

namespace App\Actions\Database;

use App\Models\Database;
use App\Models\DatabaseUser;
use Illuminate\Validation\ValidationException;

class LinkUser
{
    /**
     * @throws ValidationException
     */
    public function link(DatabaseUser $databaseUser, array $databases): void
    {
        $dbs = Database::query()
            ->where('server_id', $databaseUser->server_id)
            ->whereIn('name', $databases)
            ->count();
        if (count($databases) !== $dbs) {
            throw ValidationException::withMessages(['databases' => __('Databases not found!')])
                ->errorBag('linkUser');
        }

        $databaseUser->databases = $databases;
        $databaseUser->unlinkUser();
        $databaseUser->linkUser();
        $databaseUser->save();
    }
}
