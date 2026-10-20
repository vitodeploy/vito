<?php

namespace App\Actions\EnvironmentVariable;

use App\Actions\Environment\MarkEnvironmentForRedeploy;
use App\Models\Application;
use App\Models\Environment;
use App\Models\EnvironmentVariable;
use Dotenv\Exception\InvalidFileException;
use Dotenv\Parser\Parser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ImportEnvironmentVariables
{
    /**
     * @return list<array{key: string, is_secret: bool}>
     */
    public function preview(string $content): array
    {
        return array_map(fn (string $key): array => [
            'key' => $key,
            'is_secret' => \App\Helpers\EnvParser::isSecretKey($key) || str_ends_with($key, '_URL'),
        ], array_keys($this->parse($content)));
    }

    /**
     * @param array<string, mixed> $input
     * @return list<array{key: string, is_secret: bool}>
     */
    public function previewInput(array $input): array
    {
        Validator::make($input, ['content' => ['required', 'string']])->validate();

        return $this->preview($input['content']);
    }

    /**
     * @param array<string, mixed> $input
     */
    public function import(Environment $environment, array $input): void
    {
        Validator::make($input, ['content' => ['required', 'string'], 'secret_keys' => ['present', 'array']])->validate();
        $variables = $this->parse($input['content']);
        Validator::make($input, ['secret_keys.*' => ['required', 'string', 'distinct', Rule::in(array_keys($variables))]])->validate();

        DB::transaction(function () use ($environment, $input, $variables): void {
            Application::query()->lockForUpdate()->findOrFail($environment->application_id);
            $environment = Environment::query()->lockForUpdate()->findOrFail($environment->id);
            foreach ($variables as $key => $value) {
                app(UpsertEnvironmentVariable::class)->upsert($environment, [
                    'key' => $key,
                    'value' => $value,
                    'is_secret' => in_array($key, $input['secret_keys'], true),
                ], $environment->variables()->where('key', $key)->first(), markForRedeploy: false);
            }
            if ($variables !== []) {
                app(MarkEnvironmentForRedeploy::class)->mark($environment);
            }
        });
    }

    /**
     * @return array<string, string>
     */
    private function parse(string $content): array
    {
        $variables = [];
        $buffer = '';
        $start = 1;
        $parser = new Parser;

        foreach (explode("\n", str_replace(["\r\n", "\r"], "\n", $content)) as $index => $line) {
            if (str_contains($line, "\0")) {
                throw ValidationException::withMessages(['content' => 'Invalid env file at line '.($index + 1).'.']);
            }
            if ($buffer === '') {
                $start = $index + 1;
                if (trim($line) === '' || str_starts_with(ltrim($line), '#')) {
                    continue;
                }
            }
            $buffer .= ($buffer === '' ? '' : "\n").$line;

            try {
                $entries = $parser->parse($buffer);
            } catch (InvalidFileException) {
                throw ValidationException::withMessages(['content' => "Invalid env file at line {$start}."]);
            }
            if ($entries === []) {
                continue;
            }
            foreach ($entries as $entry) {
                $key = $entry->getName();
                if (preg_match('/\A[A-Z_][A-Z0-9_]*\z/', $key) !== 1 || strlen($key) > 255 || array_key_exists($key, $variables)) {
                    throw ValidationException::withMessages(['content' => "Invalid or duplicate variable key at line {$start}."]);
                }
                if (preg_match('/'.EnvironmentVariable::RESERVED_KEY_PATTERN.'/', $key) === 1) {
                    throw ValidationException::withMessages(['content' => "Reserved command shell key at line {$start}."]);
                }
                $variables[$key] = $entry->getValue()->isDefined() ? $entry->getValue()->get()->getChars() : '';
            }
            $buffer = '';
        }
        if ($buffer !== '') {
            throw ValidationException::withMessages(['content' => "Unclosed quoted value at line {$start}."]);
        }

        return $variables;
    }
}
