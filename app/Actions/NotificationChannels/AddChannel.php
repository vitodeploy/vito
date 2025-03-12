<?php

namespace App\Actions\NotificationChannels;

use App\Models\NotificationChannel;
use App\Models\User;
use Exception;
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
        $channel = new NotificationChannel([
            'user_id' => $user->id,
            'provider' => $input['provider'],
            'label' => $input['label'],
            'project_id' => isset($input['global']) && $input['global'] ? null : $user->current_project_id,
        ]);
        $channel->data = $channel->provider()->createData($input);
        $channel->save();

        try {
            if (! $channel->provider()->connect()) {
                $channel->delete();

                if ($channel->provider === \App\Enums\NotificationChannel::EMAIL) {
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

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function rules(array $input): array
    {
        $rules = [
            'provider' => [
                'required',
                Rule::in(config('core.notification_channels_providers')),
            ],
            'label' => 'required',
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

        $notificationChannel = new NotificationChannel([
            'provider' => $input['provider'],
        ]);

        return $notificationChannel->provider()->createRules($input);
    }
}
