<?php

namespace App\Actions\SourceControl;

use App\Models\SourceControl;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ConnectSourceControl
{
    public function connect(array $input): void
    {
        $this->validate($input);
        $sourceControl = new SourceControl([
            'provider' => $input['provider'],
            'profile' => $input['name'],
            'access_token' => $input['token'],
            'url' => Arr::has($input, 'url') ? $input['url'] : null,
        ]);

        if (! $sourceControl->provider()->connect()) {
            throw ValidationException::withMessages([
                'token' => __('Cannot connect to :provider or invalid token!', ['provider' => $sourceControl->provider]
                ),
            ]);
        }

        $sourceControl->save();
    }

    /**
     * @throws ValidationException
     */
    private function validate(array $input): void
    {
        $rules = [
            'provider' => [
                'required',
                Rule::in(\App\Enums\SourceControl::getValues()),
            ],
            'name' => [
                'required',
            ],
            'token' => [
                'required',
            ],
            'url' => [
                'nullable',
                'url:http,https',
                'ends_with:/',
            ],
        ];
        Validator::make($input, $rules)->validate();
    }
}
