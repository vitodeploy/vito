<?php

namespace App\Actions\Redirect;

use App\Enums\RedirectStatus;
use App\Jobs\Redirect\CreateJob;
use App\Models\Redirect;
use App\Models\Site;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UpdateRedirect
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function update(Site $site, Redirect $redirect, array $input): Redirect
    {
        $this->validate($site, $redirect, $input);

        $redirect->from = $input['from'];
        $redirect->to = $input['to'];
        $redirect->mode = $input['mode'];
        $redirect->websocket = ((int) $input['mode'] === Redirect::MODE_PROXY) && ($input['websocket'] ?? false);
        $redirect->status = RedirectStatus::CREATING;
        $redirect->save();

        dispatch(new CreateJob($site, $redirect))->onQueue('ssh');

        return $redirect->refresh();
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function validate(Site $site, Redirect $redirect, array $input): void
    {
        $rules = [
            'from' => [
                'required',
                'string',
                'max:255',
                'not_regex:/^http(s)?:\/\//',
                Rule::unique('redirects', 'from')->where('site_id', $site->id)->ignore($redirect->id),
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
                    Redirect::MODE_PROXY,
                ]),
            ],
            'websocket' => [
                'nullable',
                'boolean',
            ],
        ];

        Validator::make($input, $rules)->validate();
    }
}
