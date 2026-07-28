<?php

namespace App\Actions\Site;

use App\Helpers\EnvParser;
use Illuminate\Support\Facades\Validator;

class StringifyEnv
{
    /**
     * @param  array<string, mixed>  $input
     * @return array{env: string}
     */
    public function stringify(array $input): array
    {
        Validator::make($input, [
            'variables' => ['present', 'array', 'max:1000'],
            'variables.*.key' => ['required', 'string'],
            'variables.*.value' => ['nullable', 'string'],
        ])->validate();

        $variables = array_map(
            fn (array $variable): array => [
                'key' => (string) $variable['key'],
                'value' => (string) ($variable['value'] ?? ''),
            ],
            $input['variables'] ?? [],
        );

        return [
            'env' => EnvParser::stringify($variables),
        ];
    }
}
