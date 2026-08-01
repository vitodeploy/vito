<?php

use App\DNSProviders\AbstractDNSProvider;
use App\NotificationChannels\AbstractNotificationChannel;
use App\ServerProviders\AbstractProvider;
use App\Services\AbstractService;
use App\SiteTypes\AbstractSiteType;
use App\SourceControlProviders\AbstractSourceControlProvider;
use App\StorageProviders\AbstractStorageProvider;
use App\Tooling\AbstractTooling;
use App\WorkflowActions\AbstractWorkflowAction;
use Forjed\InertiaTable\Table;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Notifications\Notification;
use Tests\ArchTestCase;

/**
 * Each source namespace needs its own expectation — Pest's negative dependency
 * expectations silently pass when given an array of source namespaces.
 */
const PRESENTATION_LAYER = ['App\Http\Controllers', 'Inertia\Inertia'];

arch('server providers extend the abstract server provider')
    ->expect('App\ServerProviders')
    ->classes()
    ->toExtend(AbstractProvider::class);

arch('dns providers extend the abstract dns provider')
    ->expect('App\DNSProviders')
    ->classes()
    ->toExtend(AbstractDNSProvider::class);

arch('source control providers extend the abstract source control provider')
    ->expect('App\SourceControlProviders')
    ->classes()
    ->toExtend(AbstractSourceControlProvider::class);

arch('storage providers extend the abstract storage provider')
    ->expect('App\StorageProviders')
    ->classes()
    ->toExtend(AbstractStorageProvider::class);

arch('services extend the abstract service')
    ->expect('App\Services')
    ->classes()
    ->toExtend(AbstractService::class);

arch('site types extend the abstract site type')
    ->expect('App\SiteTypes')
    ->classes()
    ->toExtend(AbstractSiteType::class);

arch('workflow actions extend the abstract workflow action')
    ->expect('App\WorkflowActions')
    ->classes()
    ->toExtend(AbstractWorkflowAction::class);

arch('tooling extends the abstract tooling')
    ->expect('App\Tooling')
    ->classes()
    ->toExtend(AbstractTooling::class)
    ->ignoring(ArchTestCase::except('extension-points.non-tooling-classes'));

arch('notification channels extend the abstract notification channel')
    ->expect('App\NotificationChannels')
    ->classes()
    ->toExtend(AbstractNotificationChannel::class)
    ->ignoring(ArchTestCase::except('extension-points.non-channel-classes'));

arch('notifications extend the framework notification')
    ->expect('App\Notifications')
    ->classes()
    ->toExtend(Notification::class);

arch('validation rules implement the validation rule contract')
    ->expect('App\ValidationRules')
    ->classes()
    ->toImplement(ValidationRule::class);

arch('tables extend the inertia table')
    ->expect('App\Tables')
    ->classes()
    ->toExtend(Table::class);

arch('tables are suffixed with Table')
    ->expect('App\Tables')
    ->classes()
    ->toHaveSuffix('Table');

arch('console commands extend the framework command')
    ->expect('App\Console\Commands')
    ->classes()
    ->toExtend('Illuminate\Console\Command');

arch('console commands expose a handle method')
    ->expect('App\Console\Commands')
    ->classes()
    ->toHaveMethod('handle');

arch('service providers are suffixed with ServiceProvider')
    ->expect('App\Providers')
    ->toHaveSuffix('ServiceProvider');

arch('dns providers do not reach into the http layer')
    ->expect('App\DNSProviders')
    ->not->toUse(PRESENTATION_LAYER);

arch('server providers do not reach into the http layer')
    ->expect('App\ServerProviders')
    ->not->toUse(PRESENTATION_LAYER);

arch('source control providers do not reach into the http layer')
    ->expect('App\SourceControlProviders')
    ->not->toUse(PRESENTATION_LAYER);

arch('storage providers do not reach into the http layer')
    ->expect('App\StorageProviders')
    ->not->toUse(PRESENTATION_LAYER);

arch('tooling does not reach into the http layer')
    ->expect('App\Tooling')
    ->not->toUse(PRESENTATION_LAYER);
