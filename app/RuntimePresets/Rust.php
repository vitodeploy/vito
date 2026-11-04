<?php

namespace App\RuntimePresets;

use App\Enums\WebMode;
use App\Support\RepositoryFiles;
use App\Tooling\RustTooling;

class Rust extends AbstractRuntimePreset
{
    public static function id(): string
    {
        return 'rust';
    }

    public static function label(): string
    {
        return 'Rust';
    }

    public function versions(): array
    {
        return RustTooling::supportedVersions();
    }

    public function tooling(): ?string
    {
        return RustTooling::id();
    }

    public function webMode(): WebMode
    {
        return WebMode::PROXY;
    }

    public function startCommand(): ?string
    {
        return './target/release/app';
    }

    public function detect(RepositoryFiles $files): ?array
    {
        if (! $files->has('Cargo.toml') || $files->has('composer.json')) {
            return null;
        }

        $file = $files->has('rust-toolchain') ? 'rust-toolchain' : 'rust-toolchain.toml';
        $constraint = $files->tomlString($file, 'toolchain', 'channel') ?? ($file === 'rust-toolchain' ? $files->get($file) : null);

        return $this->compatibleVersions($constraint);
    }
}
