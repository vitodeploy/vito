<?php

namespace App\Traits;

use Livewire\WithPagination;

trait HasCustomPaginationView
{
    use WithPagination;

    public function paginationSimpleView(): string
    {
        return 'livewire.partials.pagination';
    }
}
