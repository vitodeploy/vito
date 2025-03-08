<?php

namespace App\Actions\Site;

use App\Exceptions\SSHError;
use App\Models\Site;
use App\SSH\Git\Git;

class UpdateBranch
{
    /**
     * @throws SSHError
     */
    public function update(Site $site, array $input): void
    {
        $site->branch = $input['branch'];
        app(Git::class)->fetchOrigin($site);
        app(Git::class)->checkout($site);
        $site->save();
    }

    public static function rules(): array
    {
        return [
            'branch' => 'required',
        ];
    }
}
