<?php

namespace App\Actions\Site;

use App\Models\Site;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UpdateSiteType
{
    /**
     * @throws ValidationException
     */
    public function update(Site $site, array $input): void
    {
        $site->type = $input['type'];
        $site->save();
    }

    /**
     * @throws ValidationException
     */
    public static function rules(): array
    {
        return [
            'type' => 'required',
            Rule::in(config('core.site_types')),
        ];
    }
}
