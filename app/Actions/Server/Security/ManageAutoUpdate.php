<?php

namespace App\Actions\Server\Security;

use App\Models\Server;
use Closure;
use Cron\CronExpression;
use Illuminate\Support\Facades\Validator;

class ManageAutoUpdate
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function update(Server $server, array $input): void
    {
        $this->validate($input);

        $enabled = (bool) ($input['enabled'] ?? false);

        $server->auto_update = $enabled;
        $server->auto_update_schedule = $enabled ? $input['schedule'] : null;
        $server->save();
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function validate(array $input): void
    {
        Validator::make($input, [
            'enabled' => ['required', 'boolean'],
            'schedule' => [
                'nullable',
                'required_if:enabled,true',
                'string',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($value && ! CronExpression::isValidExpression((string) $value)) {
                        $fail('The schedule must be a valid cron expression.');
                    }
                },
            ],
        ])->validate();
    }
}
