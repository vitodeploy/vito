<?php

namespace App\Actions\SSL;

use App\Enums\SslType;
use App\Models\Site;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CreateSSL
{
    /**
     * @throws ValidationException
     */
    public function create(Site $site, array $input): void
    {
        $this->validate($input);

        if ($input['type'] == SslType::LETSENCRYPT) {
            $site->createFreeSsl();
        }

        if ($input['type'] == SslType::CUSTOM) {
            $site->createCustomSsl($input['certificate'], $input['private']);
        }
    }

    /**
     * @throws ValidationException
     */
    protected function validate(array $input): void
    {
        $rules = [
            'type' => [
                'required',
                Rule::in(SslType::getValues()),
            ],
        ];
        if (isset($input['type']) && $input['type'] == SslType::CUSTOM) {
            $rules['certificate'] = 'required';
            $rules['private'] = 'required';
        }

        Validator::make($input, $rules)->validateWithBag('createSSL');
    }
}
