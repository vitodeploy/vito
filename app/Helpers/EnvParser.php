<?php

namespace App\Helpers;

class EnvParser
{
    /**
     * Secret key patterns - keys containing these words are considered secrets
     *
     * @var array<string>
     */
    private const SECRET_PATTERNS = ['PASSWORD', 'SECRET', 'TOKEN', 'KEY', 'PRIVATE'];

    /**
     * Check if an env key should be treated as a secret
     */
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
     * Parse a raw .env string into an array of variables
     *
     * @return array<int, array{key: string, value: string, is_secret: bool}>
     */
    public static function parse(string $raw): array
    {
        $variables = [];
        $lines = explode("\n", $raw);

        foreach ($lines as $line) {
            $trimmedLine = trim($line);

            // Skip empty lines and comments
            if ($trimmedLine === '' || str_starts_with($trimmedLine, '#')) {
                continue;
            }

            // Find the first equals sign (key can't contain =, but value can)
            $equalsIndex = strpos($trimmedLine, '=');
            if ($equalsIndex === false) {
                continue;
            }

            $key = trim(substr($trimmedLine, 0, $equalsIndex));
            $value = substr($trimmedLine, $equalsIndex + 1);

            // Remove surrounding quotes if present
            if (
                (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))
            ) {
                $value = substr($value, 1, -1);
            }

            // Handle escaped newlines in quoted strings
            $value = str_replace('\\n', "\n", $value);

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
     * Convert an array of variables back to a raw .env string
     *
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

            $needsQuotes = str_contains($value, "\n") ||
                str_contains($value, ' ') ||
                str_contains($value, '"') ||
                str_contains($value, "'") ||
                str_contains($value, '#');

            if ($needsQuotes) {
                $escapedValue = str_replace(["\n", '"'], ['\\n', '\\"'], $value);
                $lines[] = "{$key}=\"{$escapedValue}\"";
            } else {
                $lines[] = "{$key}={$value}";
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Mask secret values for frontend display
     * Secret values are completely hidden (not sent to frontend)
     *
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
     * Merge incoming variables with stored variables
     * For secrets with empty values, keep the stored value
     *
     * @param  array<int, array{key: string, value: string, is_secret: bool}>  $incoming
     * @param  array<int, array{key: string, value: string, is_secret: bool}>|null  $stored
     * @return array<int, array{key: string, value: string, is_secret: bool}>
     */
    public static function mergeWithStored(array $incoming, ?array $stored): array
    {
        if ($stored === null) {
            return $incoming;
        }

        $storedMap = [];
        foreach ($stored as $variable) {
            $storedMap[$variable['key']] = $variable;
        }

        return array_map(function ($variable) use ($storedMap) {
            $key = $variable['key'];
            $isSecret = $variable['is_secret'];
            $value = $variable['value'];

            if ($isSecret && $value === '' && isset($storedMap[$key])) {
                $variable['value'] = $storedMap[$key]['value'];
            }

            return $variable;
        }, $incoming);
    }
}
