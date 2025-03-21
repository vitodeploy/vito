<?php

namespace App\Actions\Redirect;

use App\Models\Redirect;
use App\Models\Service;
use App\Models\Site;
use App\SSH\Services\Webserver\Webserver;
use Illuminate\Validation\Rule;

class CreateRedirect
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function create(Site $site, array $input): Redirect
    {
        $redirect = new Redirect;

        $redirect->site_id = $site->id;
        $redirect->from = $input['from'];
        $redirect->to = $input['to'];
        $redirect->mode = $input['mode'];
        $redirect->status = 'created'; // This field isn't currently used.

        $redirect->save();

        /** @var Service $service */
        $service = $site->server->webserver();

        /** @var Webserver $webserver */
        $webserver = $service->handler();
        $webserver->updateVHost($site);

        return $redirect;
    }

    /**
     * @return array<string, array<string>>
     */
    public static function rules(Site $site): array
    {
        return [
            'from' => ['required', 'string', 'max:255', 'not_regex:/^http(s)?:\/\//', Rule::unique('redirects', 'from')->where('site_id', $site->id)],
            'to' => ['required', 'url:http,https'],
            'mode' => ['required', 'integer', 'in:'.implode(',', [
                301,
                302,
                307,
                308,
            ])],
        ];
    }
}
