<?php

use Tests\ArchTestCase;

arch('actions do not render responses')
    ->expect('App\Actions')
    ->not->toUse([
        'Inertia\Inertia',
        'Illuminate\Http\RedirectResponse',
        'Illuminate\Routing\Redirector',
    ]);

arch('actions take arrays, not requests')
    ->expect('App\Actions')
    ->not->toUse(['Illuminate\Http\Request', 'request'])
    ->ignoring(ArchTestCase::except('actions.accepting-request'));

arch('actions do not depend on controllers')
    ->expect('App\Actions')
    ->not->toUse('App\Http\Controllers');
