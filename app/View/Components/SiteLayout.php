<?php

namespace App\View\Components;

use App\Models\Site;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class SiteLayout extends Component
{
    public function __construct(public Site $site) {}

    public function render(): View
    {
        return view('layouts.site');
    }
}
