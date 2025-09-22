<?php

namespace App\Actions\VitoBackup;

use App\Enums\VitoBackupStatus;
use App\Models\VitoBackup;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CreateVitoBackup
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function create(array $input): VitoBackup
    {
        $this->validate($input);

        $frequencyLabels = [
            '0 * * * *' => 'Hourly',
            '0 0 * * *' => 'Daily',
            '0 0 * * 0' => 'Weekly',
            '0 0 1 * *' => 'Monthly',
        ];

        $vitoBackup = new VitoBackup([
            'name' => $frequencyLabels[$input['frequency']].' Vito Backup',
            'frequency' => $input['frequency'],
            'keep_backups' => $input['keep_backups'],
            'storage_id' => $input['storage_id'],
            'status' => VitoBackupStatus::PENDING,
        ]);

        $vitoBackup->save();

        return $vitoBackup;
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
            'storage_id' => [
                'required',
                'integer',
                Rule::exists('storage_providers', 'id')->where(function ($query) {
                    $query->whereIn('provider', ['s3', 'dropbox', 'ftp']);
                }),
            ],
        ];

        Validator::make($input, $rules)->validate();
    }
}
