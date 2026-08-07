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
     * Whether the variables form can hold this content without losing anything.
     *
     * @param  array<int, array{key: string, value: string, is_secret: bool}>  $variables
     */
    public static function isRepresentable(string $content, array $variables): bool
    {
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

    /**
     * Whether a value opening with the given quote also closes it on the same
     * physical line. Anything after the closing quote (an inline comment, say)
     * is irrelevant — only an unterminated quote means the value continues onto
     * the next line. Backslash escapes apply to double quotes only.
     */
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
     * Normalise the stored secret marker into a flat list of secret keys.
     *
     * Values are NEVER stored in the database; only the list of keys the user
     * marked as secret is persisted (e.g. ['APP_KEY', 'JWT_SECRET']). Existing
     * servers from before this change stored the full variable array
     * ([{key, value, is_secret}]); this normaliser reads that legacy shape too
     * so secret classifications survive the upgrade without a data migration.
     *
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
     * Classify variables parsed from the live server .env file using the stored
     * secret-key list. The live file is always the source of truth for values;
     * the stored list only contributes the `is_secret` classification so manual
     * secret toggles survive a reload.
     *
     * When no list has ever been stored (`null` — e.g. a site that has never
     * been saved through Vito), there is no authoritative classification, so we
     * fall back to pattern auto-detection from `parse()` to avoid exposing
     * obvious secrets unmasked. Once a list exists (even empty), it is
     * authoritative so deliberately un-secreted keys are not re-masked.
     *
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
     * Merge incoming variables with the live .env file on the server.
     *
     * A masked secret arrives with an empty value; its real value is restored
     * from the live file so the secret is never lost when the form is saved
     * without re-typing it. Non-secret variables and secrets with a new value
     * are taken as-is from the incoming set.
     *
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
