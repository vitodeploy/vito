<?php

namespace App\Traits;

use LogicException;

trait HasExclusiveOwner
{
    /**
     * @return list<string>
     */
    abstract public function ownerColumns(): array;

    abstract public function requiresOwner(): bool;

    public static function bootHasExclusiveOwner(): void
    {
        static::saving(function (self $model): void {
            $attributes = $model->getAttributes();
            $columns = $model->ownerColumns();
            $missing = array_diff($columns, array_keys($attributes));

            if ($model->exists && $missing !== []) {
                $attributes += $model->newModelQuery()->whereKey($model->getKey())->firstOrFail($missing)->getAttributes();
            }

            $owners = array_filter(
                $columns,
                fn (string $column): bool => ($attributes[$column] ?? null) !== null,
            );

            if (count($owners) > 1 || ($model->requiresOwner() && count($owners) === 0)) {
                throw new LogicException('Invalid owner for '.static::class.'.');
            }
        });
    }
}
