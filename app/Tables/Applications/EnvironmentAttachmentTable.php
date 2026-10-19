<?php

namespace App\Tables\Applications;

use App\Http\Resources\EnvironmentAttachmentResource;
use App\Models\EnvironmentAttachment;
use Forjed\InertiaTable\Column;
use Forjed\InertiaTable\Columns\ActionsColumn;
use Forjed\InertiaTable\Columns\EnumColumn;
use Forjed\InertiaTable\Columns\TextColumn;
use Forjed\InertiaTable\Table;

class EnvironmentAttachmentTable extends Table
{
    protected function query(): void
    {
        $this->perPage = config('web.pagination_size');
        $this->query->with('server', 'attachable')->orderBy('id');
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('alias', 'Alias')->fallback('Default'),
            TextColumn::make('kind', 'Kind')->value(fn (EnvironmentAttachment $attachment): string => $attachment->kind() === 'database' ? 'Database' : 'Redis / Valkey'),
            TextColumn::make('attachable.name', 'Resource'),
            TextColumn::make('server.name', 'Server'),
            EnumColumn::make('status', 'Status'),
            TextColumn::make('last_error', 'Error'),
            Column::data('id'),
            Column::data('attachment', fn (EnvironmentAttachment $attachment): array => (new EnvironmentAttachmentResource($attachment))->resolve()),
            ActionsColumn::make(),
        ];
    }
}
