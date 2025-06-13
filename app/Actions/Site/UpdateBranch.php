<?php

namespace App\Actions\Site;

use App\Exceptions\SSHError;
use App\Models\Site;
use App\SSH\OS\Git;
use Illuminate\Support\Facades\Validator;

class UpdateBranch
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws SSHError
     */
    public function update(Site $site, array $input): void
    {
        Validator::make($input, self::rules())->validate();

        $site->branch = $input['branch'];
        app(Git::class)->fetchOrigin($site);
        app(Git::class)->checkout($site);
        $site->save();
    }

    /**
     * @return array<string, string>
     */
    public static function rules(): array
    {
        return [
            'branch' => 'required',
        ];
    }
}
