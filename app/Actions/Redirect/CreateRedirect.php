<?php

namespace App\Actions\Redirect;

use App\Enums\RedirectStatus;
use App\Models\Redirect;
use App\Models\Service;
use App\Models\Site;
use App\Services\Webserver\Webserver;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CreateRedirect
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function create(Site $site, array $input): Redirect
    {
        Validator::make($input, self::rules($site))->validate();

        $redirect = new Redirect;

        $redirect->site_id = $site->id;
        $redirect->from = $input['from'];
        $redirect->to = $input['to'];
        $redirect->mode = $input['mode'];
        $redirect->status = RedirectStatus::CREATING;
        $redirect->save();

        dispatch(function () use ($site, $redirect): void {
            /** @var Service $service */
            $service = $site->server->webserver();
            /** @var Webserver $webserver */
            $webserver = $service->handler();
            $webserver->updateVHost($site, regenerate: [
                'redirects',
            ]);
            $redirect->status = RedirectStatus::READY;
            $redirect->save();
        })
            ->catch(function () use ($redirect): void {
                $redirect->status = RedirectStatus::FAILED;
                $redirect->save();
            })
            ->onConnection('ssh');

        return $redirect->refresh();
    }

    /**
     * @return array<string, array<string>>
     */
    public static function rules(Site $site): array
    {
        return [
            'from' => [
                'required',
                'string',
                'max:255',
                'not_regex:/^http(s)?:\/\//',
                Rule::unique('redirects', 'from')->where('site_id', $site->id),
            ],
            'to' => [
                'required',
                'url:http,https',
            ],
            'mode' => [
                'required',
                'integer',
                Rule::in([
                    301,
                    302,
                    307,
                    308,
                ]),
            ],
        ];
    }
}
