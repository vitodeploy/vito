<?php

namespace App\Actions\Site;

use App\Exceptions\SSHError;
use App\Models\Site;
use App\SSH\OS\Git;
use Illuminate\Support\Facades\Validator;

class UpdateBranch
{
    /**
     * With modern deployment the site path is the live release, so the branch
     * is switched in the source checkout and the next deployment clones it.
     *
     * @param  array<string, mixed>  $input
     *
     * @throws SSHError
     */
    public function update(Site $site, array $input): void
    {
        Validator::make($input, [
            'branch' => 'required',
        ])->validate();

        $site->branch = $input['branch'];
        $path = $site->modernDeploymentEnabled() ? $site->basePath().'/source' : null;
        app(Git::class)->fetchOrigin($site, $path);
        app(Git::class)->checkout($site, $path);
        $site->save();
    }
}
