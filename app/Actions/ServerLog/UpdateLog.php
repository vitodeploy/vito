<?php

namespace App\Actions\ServerLog;

use App\Models\ServerLog;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UpdateLog
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function update(ServerLog $serverLog, array $input): void
    {
        Validator::make($input, [
            'path' => 'required',
        ])->validate();

        $serverLog->update([
            'name' => $input['path'],
        ]);
    }
}
