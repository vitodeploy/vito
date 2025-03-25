<?php

namespace App\Actions\SSL;

use App\Models\Ssl;

class UpdateSSL
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Ssl $ssl, array $data): void
    {
        $data['domains'] = \array_unique(array_values($data['domains']));

        $ssl->site->resetSslDomains($data['domains']);

        $ssl->update($data);
        $ssl->site->webserver()->updateVHost($ssl->site);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function rules(array $input): array
    {
        return [
            'domains' => ['sometimes', 'array', 'min:1'],
            'domains.*' => ['required', 'string', 'max:255'],
        ];
    }
}
