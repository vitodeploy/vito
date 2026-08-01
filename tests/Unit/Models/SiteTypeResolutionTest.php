<?php

use App\Models\Site;
use App\SiteTypes\BunSite;
use App\SiteTypes\NodeJS;
use App\SiteTypes\NodeSite;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('legacy mise bun rows resolve to bun site', function () {
    /** @var Site $site */
    $site = Site::factory()->create([
        'server_id' => $this->server->id,
        'type' => 'mise_bun',
    ]);

    expect($site->type())->toBeInstanceOf(BunSite::class);
});

test('legacy mise nodejs rows resolve to node site', function () {
    /** @var Site $site */
    $site = Site::factory()->create([
        'server_id' => $this->server->id,
        'type' => 'mise_nodejs',
    ]);

    expect($site->type())->toBeInstanceOf(NodeSite::class);
});

test('deprecated nodejs install throws', function () {
    /** @var Site $site */
    $site = Site::factory()->create([
        'server_id' => $this->server->id,
        'type' => NodeJS::id(),
    ]);

    $this->expectException(RuntimeException::class);
    $this->expectExceptionMessageMatches('/deprecated/i');

    $site->type()->install();
});
