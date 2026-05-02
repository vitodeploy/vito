<?php

namespace App\Actions\Site;

use App\Models\Service;
use App\Models\Site;
use App\Services\Webserver\Webserver;
use App\Traits\NormalizesWebDirectory;
use Illuminate\Support\Facades\Validator;

class UpdateWebDirectory
{
    use NormalizesWebDirectory;

    /**
     * @param  array<string, mixed>  $input
     */
    public function update(Site $site, array $input): void
    {
        $this->validate($input);

        $site->web_directory = $this->normalizeWebDirectory($input['web_directory'] ?? null);

        /** @var Service $service */
        $service = $site->server->webserver();

        /** @var Webserver $webserver */
        $webserver = $service->handler();
        $webserver->updateVHost($site, regenerate: [
            'core',
        ], restart: false);

        $site->save();
    }

    protected function validate(array $input): void
    {
        Validator::make($input, [
            'web_directory' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9._\-\/]+$/',
                'not_regex:/\.\./',
            ],
        ])->validate();
    }
}
