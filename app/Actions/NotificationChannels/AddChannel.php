<?php

namespace App\Actions\NotificationChannels;

use App\Models\NotificationChannel;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AddChannel
{
    /**
     * @throws ValidationException
     */
    public function add(User $user, array $input): void
    {
        $this->validate($input);
        $channel = new NotificationChannel([
            'user_id' => $user->id,
            'provider' => $input['provider'],
            'label' => $input['label'],
            'project_id' => isset($input['global']) && $input['global'] ? null : $user->current_project_id,
        ]);
        $this->validateType($channel, $input);
        $channel->data = $channel->provider()->createData($input);
        $channel->save();

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

        $channel->connected = true;
        $channel->save();
    }

    /**
     * @throws ValidationException
     */
    protected function validate(array $input): void
    {
        Validator::make($input, [
            'provider' => 'required|in:'.implode(',', config('core.notification_channels_providers')),
            'label' => 'required',
        ])->validate();
    }

    /**
     * @throws ValidationException
     */
    protected function validateType(NotificationChannel $channel, array $input): void
    {
        Validator::make($input, $channel->provider()->createRules($input))
            ->validate();
    }
}
