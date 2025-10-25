<?php

namespace App\Actions\Redirect;

use App\Enums\RedirectStatus;
use App\Jobs\Redirect\CreateJob;
use App\Models\Redirect;
use App\Models\Site;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CreateRedirect
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function create(Site $site, array $input): Redirect
    {
        $this->validate($site, $input);

        $redirect = new Redirect;

        $redirect->site_id = $site->id;
        $redirect->from = $input['from'];
        $redirect->to = $input['to'];
        $redirect->mode = $input['mode'];
        $redirect->status = RedirectStatus::CREATING;
        $redirect->save();

        dispatch(new CreateJob($site, $redirect))->onQueue('ssh');

        return $redirect->refresh();
    }

    private function validate(Site $site, array $input): void
    {
        $rules = [
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
                    1000,
                ]),
            ],
        ];

        Validator::make($input, $rules)->validate();
    }
}
