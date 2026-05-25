<?php

namespace App\Actions\Worker;

use App\Models\Worker;
use Illuminate\Validation\ValidationException;

class DeleteWorker
{
    /**
     * @throws ValidationException
     */
    public function delete(Worker $worker): void
    {
        if ($worker->isSiteBootstrap()) {
            throw ValidationException::withMessages([
                'name' => 'This worker is managed by its site. Delete the site to remove it.',
            ]);
        }

        $worker->delete();
    }
}
