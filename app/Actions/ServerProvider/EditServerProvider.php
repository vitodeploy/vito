<?php

namespace App\Actions\ServerProvider;

use App\DTOs\SocketEventDTO;
use App\Events\SocketEvent;
use App\Http\Resources\ServerProviderResource;
use App\Models\ServerProvider;
use App\ServerProviders\HasEditableCredentials;
use Exception;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class EditServerProvider
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function edit(ServerProvider $serverProvider, array $input, ?int $projectId): ServerProvider
    {
        $provider = $serverProvider->editableProvider();

        Validator::make($input, array_merge([
            'name' => [
                'required',
            ],
        ], $provider?->editRules($input) ?? []))->validate();

        $credentials = $provider?->editCredentials($input) ?? $serverProvider->credentials;

        if ($provider && $credentials !== $serverProvider->credentials) {
            $this->verify($serverProvider, $provider, $credentials, $input);
            $serverProvider->credentials = $credentials;
        }

        $serverProvider->profile = $input['name'];
        $serverProvider->project_id = $projectId;

        $serverProvider->save();

        SocketEvent::dispatch(new SocketEventDTO(
            $serverProvider->project_id ?? 0,
            'server-provider.updated',
            new ServerProviderResource($serverProvider),
        ));

        return $serverProvider;
    }

    /**
     * Errors on fields the edit form doesn't render are reported on `provider`,
     * so they are still shown to the user.
     *
     * @param  array<string, mixed>  $credentials
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    private function verify(ServerProvider $serverProvider, HasEditableCredentials $provider, array $credentials, array $input): void
    {
        try {
            $provider->connect($credentials);
        } catch (ValidationException $e) {
            $fields = array_keys($provider->editRules($input));
            $errors = [];

            foreach ($e->errors() as $field => $messages) {
                $key = in_array($field, $fields, true) ? $field : 'provider';
                $errors[$key] = [...($errors[$key] ?? []), ...$messages];
            }

            throw ValidationException::withMessages($errors);
        } catch (Exception) {
            throw ValidationException::withMessages([
                'provider' => [
                    sprintf("Couldn't connect to %s. Please check your credentials.", $serverProvider->provider),
                ],
            ]);
        }
    }
}
