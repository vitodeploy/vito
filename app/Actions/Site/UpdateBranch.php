<?php

namespace App\Actions\Site;

use App\Models\Site;
use App\SSH\Git\Git;
use Illuminate\Validation\ValidationException;

class UpdateBranch
{
    /**
     * @throws ValidationException
     */
    public function update(Site $site, array $input): void
    {
        $site->branch = $input['branch'];
        app(Git::class)->checkout($site);
        $site->save();
    }

    /**
     * @throws ValidationException
     */
    public static function rules(): array
    {
        return [
            'branch' => 'required',
        ];
    }
}
