<?php

namespace App\SiteFeatures\LaravelOctane;

use App\DTOs\DynamicField;
use App\DTOs\DynamicForm;
use App\SiteFeatures\Action;
use Illuminate\Http\Request;

class Disable extends Action
{
    public function name(): string
    {
        return 'Disable';
    }

    public function active(): bool
    {
        return data_get($this->site->type_data, 'octane', false);
    }

    public function form(): ?DynamicForm
    {
        return DynamicForm::make([
            DynamicField::make('port')
                ->text()
                ->default(8000),
        ]);
    }

    public function handle(Request $request): void
    {
        // TODO: Implement handle() method.
    }
}
