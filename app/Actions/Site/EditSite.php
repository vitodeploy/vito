<?php

namespace App\Actions\Site;

use App\Models\Site;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class EditSite
{
    /**
     * @throws ValidationException
     */
    public function handle(Site $site, array $input): Site
    {
        // validate type
        $this->validateType($site, $input);

        // set type data
        $site->type_data = $site->type()->data($input);

        // save
        $site->port = $input['port'] ?? null;
        $site->save();

        // edit
        $site->type()->edit();

        return $site;
    }

    /**
     * @throws ValidationException
     */
    private function validateType(Site $site, array $input): void
    {
        $rules = $site->type()->editValidationRules($input);

        Validator::make($input, $rules)->validateWithBag('editSite');
    }
}
