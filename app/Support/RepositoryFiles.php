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
     * @return array<string, mixed>
     */
    public function json(string $path): array
    {
        $decoded = json_decode($this->get($path) ?? '', true);

        return is_array($decoded) ? $decoded : [];
    }
}
