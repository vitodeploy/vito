<?php

namespace App\Actions\Site;

use App\Models\Site;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UpdateSourceControl
{
    /**
     * @throws ValidationException
     */
    public function update(Site $site, array $input): void
    {
        $this->validate($input);

        $site->source_control_id = $input['source_control'];
        $site->save();
    }

    /**
     * @throws ValidationException
     */
    protected function validate(array $input): void
    {
        Validator::make($input, [
            'source_control' => [
                'required',
                Rule::exists('source_controls', 'id'),
            ],
        ])->validate();
    }
}
