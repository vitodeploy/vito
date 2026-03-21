<?php

namespace App\Actions\Site;

use App\Models\Site;

class UpdateVhostGeneration
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function update(Site $site, array $input): void
    {
        $validated = validator($input, [
            'vhost_generation_enabled' => ['required', 'boolean'],
        ])->validate();

        $site->vhost_generation_enabled = $validated['vhost_generation_enabled'];
        $site->save();

        if ($site->vhost_generation_enabled) {
            $site->webserver()->updateVHost($site);
        }
    }
}
