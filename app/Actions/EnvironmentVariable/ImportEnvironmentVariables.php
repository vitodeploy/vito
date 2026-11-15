<?php

namespace App\Actions\EnvironmentVariable;

use App\Actions\Environment\MarkEnvironmentForRedeploy;
use App\Helpers\EnvParser;
use App\Models\Application;
use App\Models\Environment;
use App\Models\EnvironmentVariable;
use Dotenv\Parser\EntryParser;
use Dotenv\Parser\Lines;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ImportEnvironmentVariables
{
    private const MAX_CONTENT = 262144;

    /**
     * @return list<array{key: string, is_secret: bool}>
     */
    public function preview(string $content): array
    {
        return array_map(fn (string $key): array => [
            'key' => $key,
            'is_secret' => EnvParser::isSecretKey($key) || str_ends_with($key, '_URL'),
        ], array_keys($this->parse($content)));
    }

    /**
     * @param  array<string, mixed>  $input
     * @return list<array{key: string, is_secret: bool}>
     */
    public function previewInput(array $input): array
    {
        Validator::make($input, ['content' => ['required', 'string']])->validate();

        return $this->preview($input['content']);
    }

    /**
     * @param  array<string, mixed>  $input
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
     * @throws ValidationException
     */
    private function parse(string $content): array
    {
        if (strlen($content) > self::MAX_CONTENT) {
            throw ValidationException::withMessages(['content' => 'The env file can be at most '.self::MAX_CONTENT.' bytes.']);
        }

        $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $content));
        foreach ($lines as $index => $line) {
            if (str_contains($line, "\0")) {
                throw ValidationException::withMessages(['content' => 'Invalid env file at line '.($index + 1).'.']);
            }
        }

        $variables = [];
        foreach ($this->entries($lines) as $start => $raw) {
            $result = EntryParser::parse($raw);
            if ($result->error()->isDefined()) {
                throw ValidationException::withMessages(['content' => "Invalid env file at line {$start}."]);
            }
            $entry = $result->success()->get();
            $key = $entry->getName();
            if (preg_match('/\A[A-Z_][A-Z0-9_]*\z/', $key) !== 1 || strlen($key) > 255 || array_key_exists($key, $variables)) {
                throw ValidationException::withMessages(['content' => "Invalid or duplicate variable key at line {$start}."]);
            }
            if (preg_match('/'.EnvironmentVariable::RESERVED_KEY_PATTERN.'/', $key) === 1) {
                throw ValidationException::withMessages(['content' => "Reserved command shell key at line {$start}."]);
            }
            $variables[$key] = $entry->getValue()->isDefined() ? $entry->getValue()->get()->getChars() : '';
        }

        return $variables;
    }

    /**
     * @param  list<string>  $lines
     * @return array<int, string>
     * @throws ValidationException
     */
    private function entries(array $lines): array
    {
        $entries = [];
        $open = null;
        $start = 0;
        foreach ($lines as $index => $line) {
            if ($open !== null) {
                $open[] = $line;
                if (Lines::process(['A="', $line]) !== []) {
                    $entries[$start] = implode("\n", $open);
                    $open = null;
                }

                continue;
            }
            if (trim($line) === '' || str_starts_with(trim($line), '#')) {
                continue;
            }
            if (Lines::process([$line]) === []) {
                [$open, $start] = [[$line], $index + 1];

                continue;
            }
            $entries[$index + 1] = $line;
        }

        if ($open !== null) {
            throw ValidationException::withMessages(['content' => "Unclosed quoted value at line {$start}."]);
        }

        return $entries;
    }
}
