<?php

namespace App\Actions\ServerProvider;

use App\Contracts\ServerProvider as ServerProviderContract;
use App\Models\ServerProvider;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CreateServerProvider
{
    /**
     * @throws ValidationException
     */
    public function create(User $user, array $input): ServerProvider
    {
        $this->validateInput($input);

        $provider = $this->getProvider($input['provider']);

        $this->validateProvider($provider, $input);

        try {
            $provider->connect($input);
        } catch (Exception) {
            throw ValidationException::withMessages([
                'provider' => [
                    __("Couldn't connect to provider. Please check your credentials and try again later."),
                ],
            ]);
        }

        $serverProvider = new ServerProvider();
        $serverProvider->user_id = $user->id;
        $serverProvider->profile = $input['name'];
        $serverProvider->provider = $input['provider'];
        $serverProvider->credentials = $provider->credentialData($input);
        $serverProvider->save();

        return $serverProvider;
    }

    private function getProvider($name): ServerProviderContract
    {
        $providerClass = config('core.server_providers_class.'.$name);

        return new $providerClass();
    }

    /**
     * @throws ValidationException
     */
    private function validateInput(array $input): void
    {
        Validator::make($input, [
            'name' => [
                'required',
            ],
            'provider' => [
                'required',
                Rule::in(config('core.server_providers')),
                Rule::notIn('custom'),
            ],
        ])->validate();
    }

    /**
     * @throws ValidationException
     */
    private function validateProvider(ServerProviderContract $provider, array $input): void
    {
        Validator::make($input, $provider->credentialValidationRules($input))->validate();
    }
}
