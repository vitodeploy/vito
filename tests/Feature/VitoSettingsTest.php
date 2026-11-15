<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('export settings', function () {
    $this->actingAs($this->user);

    $response = $this->get(route('vito-settings.export'))
        ->assertDownload('vito-backup-'.date('Y-m-d').'.zip');

    expect(is_dir(dirname($response->baseResponse->getFile()->getPathname())))->toBeFalse();
});

test('export is disabled in demo mode', function () {
    config(['app.demo' => true]);
    $this->actingAs($this->user);

    $this->get(route('vito-settings.export'))->assertForbidden();
});
