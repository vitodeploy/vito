<?php

namespace App\Tables;

use App\Models\Redirect;

class RedirectTable extends AbstractTable
{
    protected string $pageName = 'redirectsPage';

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
            Column::make('server_id', '')
                ->hidden()
                ->value(fn (Redirect $redirect) => $redirect->site->server_id),
            Column::make('site_id', '')
                ->hidden(),
            Column::make('id', '')
                ->hidden(),
        ];
    }
}
