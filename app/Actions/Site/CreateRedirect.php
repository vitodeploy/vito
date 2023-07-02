<?php

namespace App\Actions\Site;

use App\Models\Redirect;
use App\Models\Site;
use Exception;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CreateRedirect
{
    /**
     * @throws Exception
     */
    public function handle(Site $site, array $input): void
    {
        $this->validate($input);

        $redirect = new Redirect([
            'site_id' => $site->id,
            'mode' => $input['mode'],
            'from' => $input['from'],
            'to' => $input['to'],
        ]);
        $redirect->save();
        $redirect->addToServer();
    }

    /**
     * @throws ValidationException
     */
    private function validate(array $input): void
    {
        $rules = [
            'mode' => [
                'required',
                'in:301,302',
            ],
            'from' => [
                'required',
            ],
            'to' => [
                'required',
                'url',
            ],
        ];

        Validator::make($input, $rules)->validateWithBag('createRedirect');
    }
}
