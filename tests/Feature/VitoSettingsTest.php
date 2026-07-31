<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('export settings', function () {
    $this->actingAs($this->user);

    $this->get(route('vito-settings.export'))
        ->assertDownload('vito-backup-'.date('Y-m-d').'.zip');
});
