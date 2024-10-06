<?php

namespace App\Actions\ServerProvider;

use App\Models\ServerProvider;
use App\Models\User;
use App\ServerProviders\ServerProvider as ServerProviderContract;
use Exception;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CreateServerProvider
{
    /**
     * @throws ValidationException
     */
    public function create(User $user, array $input): ServerProvider
    {
        $provider = static::getProvider($input['provider']);

        try {
            $provider->connect($input);
        } catch (Exception) {
            throw ValidationException::withMessages([
                'provider' => [
                    sprintf("Couldn't connect to %s. Please check your credentials.", $input['provider']),
                ],
            ]);
        }

        $serverProvider = new ServerProvider;
        $serverProvider->user_id = $user->id;
        $serverProvider->profile = $input['name'];
        $serverProvider->provider = $input['provider'];
        $serverProvider->credentials = $provider->credentialData($input);
        $serverProvider->project_id = isset($input['global']) && $input['global'] ? null : $user->current_project_id;
        $serverProvider->save();

        return $serverProvider;
    }

    private static function getProvider($name): ServerProviderContract
    {
        $providerClass = config('core.server_providers_class.'.$name);

        return new $providerClass;
    }

    public static function rules(): array
    {
        return [
            'name' => [
                'required',
            ],
            'provider' => [
                'required',
                Rule::in(config('core.server_providers')),
                Rule::notIn('custom'),
            ],
        ];
    }

    public static function providerRules(array $input): array
    {
        return static::getProvider($input['provider'])->credentialValidationRules($input);
    }
}
