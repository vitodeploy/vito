<?php

namespace App\Support;

use Closure;

/**
 * Reads repository files on demand, so detection only requests the files it needs.
 */
final class RepositoryFiles
{
    private const int MAX_BYTES = 1024 * 1024;

    /** @var array<string, ?string> */
    private array $contents = [];

    /**
     * @param  Closure(string): ?string  $read
     */
    public function __construct(private readonly Closure $read) {}

    public function has(string $path): bool
    {
        return $this->get($path) !== null;
    }

    public function get(string $path): ?string
    {
        if (! array_key_exists($path, $this->contents)) {
            $contents = ($this->read)($path);
            $this->contents[$path] = $contents !== null && strlen($contents) <= self::MAX_BYTES ? trim($contents) : null;
        }

        return $this->contents[$path];
    }

    /**
     * Reads single-line quoted hints from a TOML table, ignoring multiline string bodies.
     */
    public function tomlString(string $path, string $table, string $key): ?string
    {
        $contents = preg_replace(
            <<<'REGEX'
            /"""(?:\\.|(?!""").)*"""|'''.*?'''|(?:"(?:\\.|[^"\\\r\n])*"|'[^'\r\n]*'|#[^\r\n]*)(*SKIP)(*F)/s
            REGEX,
            '',
            $this->get($path) ?? '',
        ) ?? '';
        if (! preg_match('/^\h*\[\h*'.preg_quote($table, '/').'\h*\]\h*(?:#[^\r\n]*)?\r?$(.*?)(?=^\h*\[|\z)/ms', $contents, $section)) {
            return null;
        }
        if (! preg_match('/^\h*'.preg_quote($key, '/').'\h*=\h*(["\'])([^"\'\r\n]*)\1\h*(?:#[^\r\n]*)?\r?$/m', $section[1], $value)) {
            return null;
        }

        return $value[2];
    }

    /**
     * @return array<string, mixed>
     */
    public function json(string $path): array
    {
        $decoded = json_decode($this->get($path) ?? '', true);

        return is_array($decoded) ? $decoded : [];
    }
}
