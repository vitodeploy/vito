<?php

namespace App\Actions\Site;

use App\Models\Site;

class UpdateVhostTemplate
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function update(Site $site, array $input): void
    {
        $validated = validator($input, [
            'template' => ['required', 'string', 'max:65535'],
        ])->validate();

        $site->vhost_template = $validated['template'];
        $site->save();

        $site->webserver()->updateVHost($site);
    }
}
