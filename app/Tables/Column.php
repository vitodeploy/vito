<?php

namespace App\Tables;

use App\Contracts\VitoEnum;
use Closure;

class Column
{
    protected string $name;

    protected string $header;

    protected ?string $accessor = null;

    protected bool $sortable = false;

    protected bool $hidden = false;

    /** @var array<int, array<string, mixed>> */
    protected array $displays = [];

    /** @var array<int, array{key: string, resolver: Closure}> */
    protected array $displayResolvers = [];

    protected ?Closure $valueResolver = null;

    public function __construct(string $name, string $header)
    {
        $this->name = $name;
        $this->header = $header;
    }

    public static function make(string $name, string $header): self
    {
        return new self($name, $header);
    }

    /**
     * Shortcut to create a hidden data column. Optionally accepts a value closure.
     */
    public static function data(string $name, ?Closure $value = null): self
    {
        $col = (new self($name, ''))->hidden();

        if ($value) {
            $col->value($value);
        }

        return $col;
    }

    public function accessor(string $accessor): static
    {
        $this->accessor = $accessor;

        return $this;
    }

    public function sortable(bool $sortable = true): static
    {
        $this->sortable = $sortable;

        return $this;
    }

    public function hidden(bool $hidden = true): static
    {
        $this->hidden = $hidden;

        return $this;
    }

    public function value(Closure $resolver): static
    {
        $this->valueResolver = $resolver;

        return $this;
    }

    public function text(string|Closure|null $value = null): static
    {
        $display = ['type' => 'text'];

        $this->resolveDisplayValue($display, $value, 'key');

        $this->displays[] = $display;

        return $this;
    }

    /**
     * @param  string|Closure|null  $value  The badge text value (closure, static string, or null for column value)
     * @param  ?string  $colorField  Row data field that provides the badge color/variant
     * @param  ?string  $variant  Static badge variant (e.g., 'outline', 'info')
     * @param  string|Closure|null  $tooltip  Tooltip text (closure, static string, or null for no tooltip)
     */
    public function badge(
        string|Closure|null $value = null,
        ?string $colorField = null,
        ?string $variant = null,
        string|Closure|null $tooltip = null,
    ): static {
        $display = ['type' => 'badge'];

        if ($colorField !== null) {
            $display['color_field'] = $colorField;
        }

        if ($variant !== null) {
            $display['variant'] = $variant;
        }

        $this->resolveDisplayValue($display, $value, 'key');
        $this->resolveDisplayValue($display, $tooltip, 'tooltip_key');

        $this->displays[] = $display;

        return $this;
    }

    public function date(string|Closure|null $value = null): static
    {
        $display = ['type' => 'date'];

        $this->resolveDisplayValue($display, $value, 'key');

        $this->displays[] = $display;

        return $this;
    }

    /**
     * @param  array<string, string>  $routeParams  Route parameters with :field tokens for dynamic values
     */
    public function link(string $routeName, array $routeParams = [], string|Closure|null $value = null): static
    {
        $display = [
            'type' => 'link',
            'route' => $routeName,
            'params' => $routeParams,
        ];

        $this->resolveDisplayValue($display, $value, 'key');

        $this->displays[] = $display;

        return $this;
    }

    public function copyable(string|Closure|null $value = null): static
    {
        $display = ['type' => 'copyable'];

        $this->resolveDisplayValue($display, $value, 'key');

        $this->displays[] = $display;

        return $this;
    }

    public function icon(string|Closure $icon): static
    {
        $display = ['type' => 'icon'];

        $this->resolveDisplayValue($display, $icon, 'key');

        $this->displays[] = $display;

        return $this;
    }

    /**
     * Automatically render a VitoEnum field as a colored badge.
     * Reads the enum from the model's attribute, extracts getText() and getColor().
     * No need for a separate hidden color column or manual value() closure.
     */
    public function enum(): static
    {
        $field = $this->accessor ?? $this->name;
        $colorKey = "_{$this->name}_enum_color";

        $this->valueResolver = fn ($model) => ($enumValue = data_get($model, $field)) instanceof VitoEnum
            ? $enumValue->getText()
            : $enumValue;

        $this->displayResolvers[] = [
            'key' => $colorKey,
            'resolver' => fn ($model) => ($enumValue = data_get($model, $field)) instanceof VitoEnum
                ? $enumValue->getColor()
                : null,
        ];

        $this->displays[] = [
            'type' => 'badge',
            'color_field' => $colorKey,
        ];

        return $this;
    }

    public function component(string $component): static
    {
        $this->displays[] = [
            'type' => 'component',
            'component' => $component,
        ];

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getHeader(): string
    {
        return $this->header;
    }

    public function getAccessor(): string
    {
        return $this->accessor ?? $this->name;
    }

    public function getSortKey(): string
    {
        return $this->accessor ?? $this->name;
    }

    public function isSortable(): bool
    {
        return $this->sortable;
    }

    public function isHidden(): bool
    {
        return $this->hidden;
    }

    /**
     * Extract the column's primary value from the model.
     */
    public function getValue(mixed $model): mixed
    {
        if ($this->valueResolver) {
            return ($this->valueResolver)($model);
        }

        return data_get($model, $this->accessor ?? $this->name);
    }

    /**
     * Resolve all display closure values for the given model and return extra row data.
     *
     * @return array<string, mixed>
     */
    public function resolveDisplayValues(mixed $model): array
    {
        $extra = [];

        foreach ($this->displayResolvers as $resolver) {
            $extra[$resolver['key']] = ($resolver['resolver'])($model);
        }

        return $extra;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'header' => $this->header,
            'sortable' => $this->sortable,
            'sort_key' => $this->getSortKey(),
            'hidden' => $this->hidden,
            'displays' => $this->displays,
        ];
    }

    /**
     * Handle a display modifier value:
     * - Closure: registers a server-side resolver, stores an auto-generated row key
     * - String: treated as a row data field name (the frontend reads row[key])
     * - Null: the frontend falls back to the column's own value (row[column.name])
     *
     * @param  array<string, mixed>  $display
     */
    protected function resolveDisplayValue(array &$display, string|Closure|null $value, string $keyName): void
    {
        if ($value instanceof Closure) {
            $index = count($this->displays);
            $autoKey = "_{$this->name}_d{$index}_{$keyName}";
            $display[$keyName] = $autoKey;
            $this->displayResolvers[] = [
                'key' => $autoKey,
                'resolver' => $value,
            ];
        } elseif (is_string($value)) {
            $display[$keyName] = $value;
        }
    }
}
