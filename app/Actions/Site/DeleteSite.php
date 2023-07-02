<?php

namespace App\Actions\Site;

use App\Models\Site;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class DeleteSite
{
    /**
     * @throws ValidationException
     */
    public function handle(Site $site, array $input): void
    {
        $this->validateDelete($input);

        $site->update(['status' => 'deleting']);

        $site->remove();
    }

    /**
     * @throws ValidationException
     */
    protected function validateDelete(array $input): void
    {
        Validator::make($input, [
            'confirm' => 'required|in:delete',
        ])->validateWithBag('deleteSite');
    }
}
