<?php

namespace App\Actions\Site;

use App\Models\Site;
use App\SSH\Git\Git;
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
        $site->branch = $input['branch'];
        app(Git::class)->checkout($site);
        $site->save();
    }

    /**
     * @throws ValidationException
     */
    protected function validate(array $input): void
    {
        Validator::make($input, [
            'branch' => 'required',
        ]);
    }
}
