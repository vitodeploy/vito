<?php

namespace App\Actions\Site;

use App\Exceptions\SSHError;
use App\Models\Site;
use Illuminate\Support\Facades\Validator;

class UpdatePort
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws SSHError
     */
    public function update(Site $site, array $input): void
    {
        $validated = Validator::make($input, [
            'port' => ['required', 'integer', 'between:1024,65535'],
        ])->validate();

        $site->port = (int) $validated['port'];
        $site->save();

        $site->webserver()->updateVHost($site);

        app(BroadcastSiteUpdate::class)->broadcast($site);
    }
}
