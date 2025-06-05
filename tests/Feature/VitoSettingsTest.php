<?php

namespace Feature;

use Tests\TestCase;

class VitoSettingsTest extends TestCase
{
    public function test_export_settings(): void
    {
        $this->actingAs($this->user);

        $this->get(route('vito-settings.export'))
            ->assertDownload('vito-backup-'.date('Y-m-d').'.zip');
    }
}
