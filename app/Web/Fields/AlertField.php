<?php

namespace App\Web\Fields;

use Filament\Forms\Components\Field;

class AlertField extends Field
{
    protected string $view = 'fields.alert';

    public string $color = 'blue';

    public string $icon = 'heroicon-o-information-circle';

    public string $message = '';

    public function color(string $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function icon(string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function message(string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function success(): static
    {
        return $this->color('green')->icon('heroicon-o-check-circle');
    }

    public function warning(): static
    {
        return $this->color('yellow')->icon('heroicon-o-exclamation-circle');
    }

    public function danger(): static
    {
        return $this->color('red')->icon('heroicon-o-x-circle');
    }

    public function info(): static
    {
        return $this->color('blue')->icon('heroicon-o-information-circle');
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
