<?php

namespace App\Actions\Site;

use App\Exceptions\SSHError;
use App\Models\Site;
use Illuminate\Support\Facades\Validator;

class UpdatePHPSettings
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws SSHError
     */
    public function update(Site $site, array $input): void
    {
        $validated = $this->validate($input);

        $typeData = $site->type_data ?? [];
        $typeData['php'] = $validated;
        $site->update(['type_data' => $typeData]);

        $site->webserver()->updateVHost($site);

        app(BroadcastSiteUpdate::class)->broadcast($site);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{max_upload_size: int|null, max_execution_time: int|null, memory_limit: int|null, max_input_vars: int|null}
     */
    private function validate(array $input): array
    {
        $validator = Validator::make($input, [
            'max_upload_size' => ['nullable', 'integer', 'min:1', 'max:10240'],
            'max_execution_time' => ['nullable', 'integer', 'min:1', 'max:3600'],
            'memory_limit' => ['nullable', 'integer', 'min:16', 'max:8192'],
            'max_input_vars' => ['nullable', 'integer', 'min:100', 'max:100000'],
        ]);

        $validator->after(function ($validator) use ($input): void {
            if ($validator->errors()->hasAny(['max_upload_size', 'memory_limit'])) {
                return;
            }

            $upload = $input['max_upload_size'] ?? null;
            $memory = $input['memory_limit'] ?? null;

            if (is_numeric($upload) && is_numeric($memory) && (int) $memory < (int) $upload) {
                $validator->errors()->add(
                    'memory_limit',
                    'The memory limit must be greater than or equal to the max upload size.'
                );
            }
        });

        $validated = $validator->validate();

        return [
            'max_upload_size' => $this->intOrNull($validated['max_upload_size'] ?? null),
            'max_execution_time' => $this->intOrNull($validated['max_execution_time'] ?? null),
            'memory_limit' => $this->intOrNull($validated['memory_limit'] ?? null),
            'max_input_vars' => $this->intOrNull($validated['max_input_vars'] ?? null),
        ];
    }

    private function intOrNull(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }
}
