<?php

namespace App\Actions\Server;

use App\Models\Server;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class DeleteServer
{
    /**
     * @throws ValidationException
     */
    public function delete(Server $server, array $input): void
    {
        $this->validateDelete($input);

        DB::transaction(function () use ($server) {
            $server->cleanDelete();
        });
    }

    /**
     * @throws ValidationException
     */
    protected function validateDelete(array $input): void
    {
        Validator::make($input, [
            'confirm' => 'required|in:delete',
        ])->validateWithBag('deleteServer');
    }
}
