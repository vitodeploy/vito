<?php

namespace App\Actions\Site;

use App\Models\Site;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UpdateBranch
{
    /**
     * @throws ValidationException
     */
    public function update(Site $site, array $input): void
    {
        $this->validate($input);

        $site->updateBranch($input['branch']);
    }

    /**
     * @throws ValidationException
     */
    protected function validate(array $input): void
    {
        Validator::make($input, [
            'branch' => 'required',
        ])->validateWithBag('updateBranch');
    }
}
