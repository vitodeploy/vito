<?php

namespace App\Actions\ServerProvider;

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
    public function create(User $user, array $input): ServerProvider
    {
        $this->validate($input);

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
        $serverProvider->project_id = isset($input['global']) && $input['global'] ? null : $user->currentProject?->id;
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

    private function validate(array $input): void
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

        Validator::make($input, array_merge($rules, $this->providerRules($input)))->validate();
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, array<string>>
     */
    private function providerRules(array $input): array
    {
        if (! isset($input['provider'])) {
            return [];
        }

        return self::getProvider($input['provider'])->credentialValidationRules($input);
    }
}
