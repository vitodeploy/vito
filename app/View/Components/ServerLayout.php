<?php

namespace App\View\Components;

use App\Models\Server;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ServerLayout extends Component
{
    public function __construct(public Server $server) {}

    public function render(): View
    {
        return view('layouts.server');
    }
}
