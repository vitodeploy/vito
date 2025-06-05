<?php

namespace Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VitoSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_settings(): void
    {
        $this->actingAs($this->user);

        $this->get(route('vito-settings.export'))
            ->assertDownload('vito-backup-'.date('Y-m-d').'.zip');
    }
}
