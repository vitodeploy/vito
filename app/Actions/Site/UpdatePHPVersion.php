<?php

namespace App\Actions\Site;

use App\Exceptions\SSHError;
use App\Models\Site;
use Illuminate\Validation\Rule;

class UpdatePHPVersion
{
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
     * @throws SSHError
     */
    public function update(Site $site, array $input): void
    {
        $site->changePHPVersion($input['version']);
    }
}
