<?php

namespace App\Actions\VitoBackup;

use App\Models\VitoBackup;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UpdateVitoBackup
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function update(VitoBackup $vitoBackup, array $input): void
    {
        $this->validate($input);

        $frequencyLabels = [
            '0 * * * *' => 'Hourly',
            '0 0 * * *' => 'Daily',
            '0 0 * * 0' => 'Weekly',
            '0 0 1 * *' => 'Monthly',
        ];

        $vitoBackup->name = $frequencyLabels[$input['frequency']].' Vito Backup';
        $vitoBackup->frequency = $input['frequency'];
        $vitoBackup->keep_backups = $input['keep_backups'];

        $vitoBackup->save();
    }

    private function validate(array $input): void
    {
        $rules = [
            'frequency' => [
                'required',
                Rule::in(['0 * * * *', '0 0 * * *', '0 0 * * 0', '0 0 1 * *']),
            ],
            'keep_backups' => [
                'required',
                'integer',
                'min:1',
                'max:100',
            ],
        ];

        Validator::make($input, $rules)->validate();
    }
}
