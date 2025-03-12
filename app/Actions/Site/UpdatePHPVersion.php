<?php

namespace App\Actions\Site;

use App\Exceptions\SSHError;
use App\Models\Site;
use Illuminate\Validation\Rule;

class UpdatePHPVersion
{
    /**
     * @return array<string, array<string>>
     */
    public static function rules(Site $site): array
    {
        return [
            'version' => [
                'required',
                Rule::exists('services', 'version')
                    ->where('server_id', $site->server_id)
                    ->where('type', 'php'),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     *
     * @throws SSHError
     */
    public function update(Site $site, array $input): void
    {
        $site->changePHPVersion($input['version']);
    }
}
