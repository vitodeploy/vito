<?php

namespace App\Helpers;

class EnvParser
{
    /**
     * @var array<string>
     */
    private const SECRET_PATTERNS = ['PASSWORD', 'SECRET', 'TOKEN', 'KEY', 'PRIVATE'];

    public const KEY_RULE = 'regex:/\A[A-Za-z0-9_.]+\z/';

    public static function isSecretKey(string $key): bool
    {
        $upperKey = strtoupper($key);

        foreach (self::SECRET_PATTERNS as $pattern) {
            if (str_contains($upperKey, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, array{key: string, value: string, is_secret: bool}>
     */
    public static function parse(string $raw): array
    {
        $variables = [];
        $lines = explode("\n", $raw);

        foreach ($lines as $line) {
            $trimmedLine = trim($line);

            if ($trimmedLine === '' || str_starts_with($trimmedLine, '#')) {
                continue;
            }

            $equalsIndex = strpos($trimmedLine, '=');
            if ($equalsIndex === false) {
                continue;
            }

            $key = trim(substr($trimmedLine, 0, $equalsIndex));
            $value = substr($trimmedLine, $equalsIndex + 1);

            $wasDoubleQuoted = strlen($value) >= 2
                && str_starts_with($value, '"')
                && str_ends_with($value, '"');

            if (
                $wasDoubleQuoted ||
                (strlen($value) >= 2 && str_starts_with($value, "'") && str_ends_with($value, "'"))
            ) {
                $value = substr($value, 1, -1);
            }

            if ($wasDoubleQuoted) {
                $value = preg_replace_callback('/\\\\(.)/s', fn (array $matches): string => match ($matches[1]) {
                    'n' => "\n",
                    'r' => "\r",
                    '"' => '"',
                    '\\' => '\\',
                    default => $matches[0],
                }, $value) ?? $value;
            }

            if ($key !== '') {
                $variables[] = [
                    'key' => $key,
                    'value' => $value,
                    'is_secret' => self::isSecretKey($key),
                ];
            }
        }

        return $variables;
    }

    /**
     * @param  array<int, array{key: string, value: string, is_secret: bool}>  $variables
     */
    public static function isRepresentable(string $content, array $variables): bool
    {
        if (preg_match('/\r(?!\n)/', $content) === 1) {
            return false;
        }

        $shapeOk = collect(explode("\n", $content))
            ->map(fn (string $line): string => trim($line))
            ->every(function (string $line): bool {
                if ($line === '' || str_starts_with($line, '#')) {
                    return true;
                }

                $index = strpos($line, '=');

                if ($index === false || preg_match('/^[^\s=]+\s*=/', $line) !== 1) {
                    return false;
                }

                $value = ltrim(substr($line, $index + 1));

                foreach (['"', "'"] as $quote) {
                    if (str_starts_with($value, $quote)) {
                        return self::closesQuote($value, $quote);
                    }
                }

                return true;
            });

        return $shapeOk && self::parse(self::stringify($variables)) === $variables;
    }

    private static function closesQuote(string $value, string $quote): bool
    {
        $escaped = false;
        $length = strlen($value);

        for ($i = 1; $i < $length; $i++) {
            if ($escaped) {
                $escaped = false;

                continue;
            }

            if ($quote === '"' && $value[$i] === '\\') {
                $escaped = true;

                continue;
            }

            if ($value[$i] === $quote) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, array{key: string, value: string}>  $variables
     */
    public static function stringify(array $variables): string
    {
        $lines = [];

        foreach ($variables as $variable) {
            $key = trim($variable['key']);
            $value = $variable['value'];

            if ($key === '') {
                continue;
            }

            $needsQuotes = preg_match('/\s/', $value) === 1
                || str_contains($value, '#')
                || str_starts_with($value, '"')
                || str_starts_with($value, "'");

            if (! $needsQuotes) {
                $lines[] = "{$key}={$value}";

                continue;
            }

            if (
                str_contains($value, '"')
                && ! str_contains($value, "'")
                && ! str_contains($value, '\\')
                && ! str_contains($value, "\n")
                && ! str_contains($value, "\r")
            ) {
                $lines[] = "{$key}='{$value}'";

                continue;
            }

            $escapedValue = str_replace(['\\', "\n", "\r", '"'], ['\\\\', '\\n', '\\r', '\\"'], $value);
            $lines[] = "{$key}=\"{$escapedValue}\"";
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<int, array{key: string, value: string}>  $variables
     */
    public static function stringifyLiteral(array $variables): string
    {
        $lines = [];
        foreach ($variables as $variable) {
            $key = $variable['key'];
            $value = str_replace(["\r\n", "\r"], "\n", $variable['value']);
            if (str_contains($value, "'") || str_contains($value, "\n") || str_contains($value, '="')) {
                $value = str_replace(['\\', '"', '$', "\n"], ['\\\\', '\\"', '\\$', '\\n'], $value);
                $lines[] = $key.'="'.$value.'"';
            } else {
                $lines[] = $key."='".$value."'";
            }
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<int, mixed>|null  $stored
     * @return array<int, string>
     */
    public static function secretKeys(?array $stored): array
    {
        if ($stored === null) {
            return [];
        }

        $keys = [];

        foreach ($stored as $entry) {
            if (is_string($entry)) {
                $keys[] = $entry;

                continue;
            }

            if (is_array($entry) && ($entry['is_secret'] ?? false) && isset($entry['key'])) {
                $keys[] = (string) $entry['key'];
            }
        }

        return array_values(array_unique($keys));
    }

    /**
     * @param  array<int, array{key: string, value: string, is_secret: bool}>  $parsed
     * @param  array<int, mixed>|null  $stored
     * @return array<int, array{key: string, value: string, is_secret: bool}>
     */
    public static function classify(array $parsed, ?array $stored): array
    {
        if ($stored === null) {
            return $parsed;
        }

        $secretKeys = array_flip(self::secretKeys($stored));

        return array_map(function ($variable) use ($secretKeys) {
            $variable['is_secret'] = isset($secretKeys[$variable['key']]);

            return $variable;
        }, $parsed);
    }

    /**
     * @param  array<int, array{key: string, value: string, is_secret: bool}>  $variables
     * @return array<int, array{key: string, value: string, is_secret: bool}>
     */
    public static function maskSecrets(array $variables): array
    {
        return array_map(function ($variable) {
            if ($variable['is_secret']) {
                $variable['value'] = '';
            }

            return $variable;
        }, $variables);
    }

    /**
     * @param  array<int, array{key: string, value: string, is_secret: bool}>  $incoming
     * @param  array<int, array{key: string, value: string, is_secret: bool}>  $live
     * @return array<int, array{key: string, value: string, is_secret: bool}>
     */
    public static function mergeWithLive(array $incoming, array $live): array
    {
        $liveMap = [];
        foreach ($live as $variable) {
            $liveMap[$variable['key']] = $variable['value'];
        }

        return array_map(function ($variable) use ($liveMap) {
            $key = $variable['key'];

            if ($variable['is_secret'] && $variable['value'] === '' && isset($liveMap[$key])) {
                $variable['value'] = $liveMap[$key];
            }

            return $variable;
        }, $incoming);
    }
}
