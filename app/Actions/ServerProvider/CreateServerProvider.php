<?php

namespace App\Actions\ServerProvider;

use App\Models\Project;
use App\Models\Server;
use App\Models\ServerProvider;
use App\Models\User;
use App\ServerProviders\ServerProvider as ServerProviderContract;
use Exception;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CreateServerProvider
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function create(User $user, Project $project, array $input): ServerProvider
    {
        Validator::make($input, self::rules($input))->validate();

        $provider = self::getProvider($input['provider']);

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

    private static function getProvider(string $name): ServerProviderContract
    {
        $providerClass = config('server-provider.providers.'.$name.'.handler');
        /** @var ServerProviderContract $provider */
        $provider = new $providerClass(new ServerProvider, new Server);

        return $provider;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function rules(array $input): array
    {
        $rules = [
            'name' => [
                'required',
            ],
            'provider' => [
                'required',
                Rule::in(array_keys(config('server-provider.providers'))),
                Rule::notIn('custom'),
            ],
        ];

        return array_merge($rules, self::providerRules($input));
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, array<string>>
     */
    private static function providerRules(array $input): array
    {
        if (! isset($input['provider'])) {
            return [];
        }

        return self::getProvider($input['provider'])->credentialValidationRules($input);
    }
}
