<?php

namespace App\Actions\ServerProvider;

use App\Models\Project;
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
    public function create(User $user, Project $project, array $input): ServerProvider
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
        $serverProvider->project_id = isset($input['global']) && $input['global'] ? null : $project->id;
        $serverProvider->save();

        return $serverProvider;
    }

    private static function getProvider($name): ServerProviderContract
    {
        $providerClass = config('core.server_providers_class.'.$name);

        return new $providerClass;
    }

    public static function rules(array $input): array
    {
        $rules = [
            'name' => [
                'required',
            ],
            'provider' => [
                'required',
                Rule::in(config('core.server_providers')),
                Rule::notIn('custom'),
            ],
        ];

        return array_merge($rules, static::providerRules($input));
    }

    private static function providerRules(array $input): array
    {
        if (! isset($input['provider'])) {
            return [];
        }

        return static::getProvider($input['provider'])->credentialValidationRules($input);
    }
}
