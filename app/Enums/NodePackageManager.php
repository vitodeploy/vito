<?php

namespace App\Enums;

use App\Contracts\VitoEnum;
use App\Traits\HasEnumHelpers;

enum NodePackageManager: string implements VitoEnum
{
    use HasEnumHelpers;

    case Npm = 'npm';
    case Pnpm = 'pnpm';
    case Yarn = 'yarn';

    public function getColor(): string
    {
        return 'default';
    }

    public function getText(): string
    {
        return match ($this) {
            self::Npm => 'npm',
            self::Pnpm => 'pnpm',
            self::Yarn => 'yarn',
        };
    }

    public function installCommand(): string
    {
        return match ($this) {
            self::Npm => 'npm ci',
            self::Pnpm => 'pnpm install --frozen-lockfile',
            self::Yarn => 'yarn install --frozen-lockfile',
        };
    }

    public function buildCommand(): string
    {
        return match ($this) {
            self::Npm => 'npm run build',
            self::Pnpm => 'pnpm run build',
            self::Yarn => 'yarn build',
        };
    }

    public function startCommand(): string
    {
        return match ($this) {
            self::Npm => 'npm start',
            self::Pnpm => 'pnpm start',
            self::Yarn => 'yarn start',
        };
    }

    public function toolId(): string
    {
        return match ($this) {
            self::Npm => 'node',
            self::Pnpm => 'pnpm',
            self::Yarn => 'yarn',
        };
    }

    public static function fromToolId(string $toolId): self
    {
        return match ($toolId) {
            'node' => self::Npm,
            'pnpm' => self::Pnpm,
            'yarn' => self::Yarn,
            default => throw new \InvalidArgumentException("No NodePackageManager for tool id: {$toolId}"),
        };
    }

    /**
     * @return array<int, string>
     */
    public static function toolIds(): array
    {
        return array_map(fn (self $case) => $case->toolId(), self::cases());
    }
}
