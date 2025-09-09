<?php

namespace App\Actions\NotificationChannels;

use App\Models\NotificationChannel;
use App\Models\User;
use App\NotificationChannels\Email;
use Exception;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AddChannel
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function add(User $user, array $input): void
    {
        $this->validate($input);

        $channel = new NotificationChannel([
            'user_id' => $user->id,
            'provider' => $input['provider'],
            'label' => $input['name'],
            'project_id' => isset($input['global']) && $input['global'] ? null : $user->current_project_id,
        ]);
        $channel->data = $channel->provider()->createData($input);
        $channel->save();

        try {
            if (! $channel->provider()->connect()) {
                $channel->delete();

                if ($channel->provider === Email::id()) {
                    throw ValidationException::withMessages([
                        'email' => __('Could not connect! Make sure you configured `.env` file correctly.'),
                    ]);
                }

                throw ValidationException::withMessages([
                    'provider' => __('Could not connect'),
                ]);
            }
        } catch (Exception $e) {
            $channel->delete();

            throw ValidationException::withMessages([
                'provider' => $e->getMessage(),
            ]);
        }

        $channel->connected = true;
        $channel->save();
    }

    private function validate(array $input): void
    {
        $rules = [
            'provider' => [
                'required',
                Rule::in(array_keys(config('notification-channel.providers'))),
            ],
            'name' => 'required',
        ];

        Validator::make($input, array_merge($rules, $this->providerRules($input)))->validate();
    }

    private function providerRules(array $input): array
    {
        if (! isset($input['provider'])) {
            return [];
        }

        $notificationChannel = new NotificationChannel([
            'provider' => $input['provider'],
        ]);

        return $notificationChannel->provider()->createRules($input);
    }
}
