<?php

namespace App\Actions\Site;

use App\Helpers\EnvParser;
use Illuminate\Support\Facades\Validator;

class ParseEnv
{
    /**
     * @param  array<string, mixed>  $input
     * @return array{variables: array<int, array{key: string, value: string, is_secret: bool}>, representable: bool}
     */
    public function parse(array $input): array
    {
        Validator::make($input, [
            'content' => ['present', 'nullable', 'string'],
        ])->validate();

        $content = (string) ($input['content'] ?? null);
        $variables = EnvParser::parse($content);

        return [
            'variables' => $variables,
            'representable' => EnvParser::isRepresentable($content, $variables),
        ];
    }
}
