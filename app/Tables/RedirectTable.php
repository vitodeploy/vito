<?php

namespace App\Tables;

use App\Models\Redirect;

class RedirectTable extends AbstractTable
{
    protected string $pageName = 'redirectsPage';

    protected ?string $realtimeEvent = 'redirect';

    /**
     * @return array<int, Column>
     */
    protected function columns(): array
    {
        return [
            Column::make('from', 'From')
                ->sortable(),
            Column::make('to', 'To')
                ->sortable(),
            Column::make('mode', 'Mode')
                ->sortable()
                ->value(fn (Redirect $redirect) => $redirect->mode === '1000' ? 'Proxy' : $redirect->mode),
            Column::make('created_at', 'Created at')
                ->sortable()
                ->date(),
            Column::make('status', 'Status')
                ->sortable()
                ->enum(),
            Column::data('server_id', fn (Redirect $redirect) => $redirect->site->server_id),
            Column::data('site_id'),
            Column::data('id'),
        ];
    }
}
