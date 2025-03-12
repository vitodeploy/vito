<?php

namespace App\Actions\SshKey;

use App\Models\SshKey;
use App\Models\User;
use App\ValidationRules\SshKeyRule;
use Illuminate\Validation\ValidationException;

class CreateSshKey
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function create(User $user, array $input): SshKey
    {
        $key = new SshKey([
            'user_id' => $user->id,
            'name' => $input['name'],
            'public_key' => $input['public_key'],
        ]);
        $key->save();

        return $key;
    }

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'name' => 'required',
            'public_key' => [
                'required',
                new SshKeyRule,
            ],
        ];
    }
}
