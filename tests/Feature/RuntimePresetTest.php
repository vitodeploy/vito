<?php

use App\Actions\Bootstrap\GetBootstrap;
use App\ComponentCapabilities\AbstractComponentCapability;
use App\ComponentCapabilities\Worker;
use App\DTOs\DynamicField;
use App\DTOs\DynamicForm;
use App\Enums\WebMode;
use App\Models\Application;
use App\Models\ComponentCapability;
use App\Plugins\RegisterComponentCapability;
use App\Plugins\RegisterRuntimePreset;
use App\RuntimePresets\AbstractRuntimePreset;
use App\RuntimePresets\Bun;
use App\RuntimePresets\Custom;
use App\RuntimePresets\Laravel;
use App\RuntimePresets\NodeJS;
use App\RuntimePresets\PHP;
use App\RuntimePresets\StaticSite;
use App\Tooling\ToolingRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('built-in runtime presets resolve and expose public bootstrap descriptors', function (string $id, string $class, WebMode $mode, ?string $tooling) {
    $preset = (new Application(['runtime' => $id]))->preset();
    $runtimes = $this->actingAs($this->user)->getJson('/bootstrap')
        ->assertSuccessful()->json('configs.application.runtimes');
    $descriptor = collect($runtimes)->firstWhere('id', $id);

    expect($preset)->toBeInstanceOf($class)
        ->and($preset::id())->toBe($id)
        ->and($preset->webMode())->toBe($mode)
        ->and($preset->tooling())->toBe($tooling)
        ->and($descriptor)->toBe([
            'id' => $id,
            'label' => $preset::label(),
            'versions' => $preset->versions(),
            'web_mode' => $mode->getText(),
            'form' => $preset->form()->toArray(),
        ])
        ->and($mode->getColor())->not->toBeEmpty();

    if ($tooling !== null) {
        $tool = ToolingRegistry::find($tooling);
        expect($tool)->not->toBeNull();
        foreach ($preset->versions() as $version) {
            expect($tool::supportedVersions())->toContain($preset->toolingVersion($version));
        }
    }
})->with([
    ['php', PHP::class, WebMode::FPM, 'composer'],
    ['node', NodeJS::class, WebMode::PROXY, 'node'],
    ['bun', Bun::class, WebMode::PROXY, 'bun'],
    ['static', StaticSite::class, WebMode::STATIC, null],
    ['custom', Custom::class, WebMode::PROXY, null],
    ['laravel', Laravel::class, WebMode::FPM, 'composer'],
]);

test('runtime requirements use the selected version without imposing server-wide mise services', function (string $id, array $services) {
    $preset = (new Application(['runtime' => $id]))->preset();

    expect($preset->requiredServices('8.4'))->toBe($services);

    if ($services !== []) {
        expect($preset->requiredServices('8.2'))->toBe(['php' => '8.2'])
            ->and($preset->versions())->toBe(config('service.services.php.versions'));
    }
})->with([
    ['php', ['php' => '8.4']],
    ['laravel', ['php' => '8.4']],
    ['node', []],
    ['bun', []],
    ['static', []],
    ['custom', []],
]);

test('suggested deployment scripts are loaded by stage without reusing site scripts', function () {
    expect((new Laravel)->defaultScripts())->toBe([
        'build' => "composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader\n",
        'pre-release' => "php artisan migrate --force\n",
        'post-release' => "php artisan optimize\n",
    ])
        ->and((new PHP)->defaultScripts()['build'])->toContain('if [ -f composer.json ]; then', 'composer install')
        ->and((new NodeJS)->defaultScripts())->toBe([
            'build' => "npm ci\nnpm run build --if-present\n",
            'pre-release' => '',
            'post-release' => '',
        ])
        ->and((new Bun)->defaultScripts()['build'])->toContain('bun install');
});

test('runtime suggestions are empty unless the preset supplies them', function () {
    foreach ([new StaticSite, new Custom] as $preset) {
        expect($preset->versions())->toBe([])
            ->and($preset->webRoot())->toBeNull()
            ->and($preset->startCommand())->toBeNull()
            ->and($preset->schedulerCommand())->toBeNull()
            ->and($preset->sharedPaths())->toBe([])
            ->and($preset->form()->toArray())->toBe([])
            ->and($preset->defaultScripts())->toBe(['build' => '', 'pre-release' => '', 'post-release' => '']);
    }

    expect((new Laravel)->webRoot())->toBe('public')
        ->and((new Laravel)->sharedPaths())->toBe(['storage'])
        ->and((new Laravel)->schedulerCommand())->toBe('php artisan schedule:run')
        ->and((new NodeJS)->startCommand())->toBe('npm start')
        ->and((new Bun)->startCommand())->toBe('bun run start');
});

test('plugins can register runtime presets and capabilities in bootstrap', function () {
    $preset = new class extends AbstractRuntimePreset
    {
        public static function id(): string
        {
            return 'python';
        }

        public static function label(): string
        {
            return 'Python';
        }

        public function webMode(): WebMode
        {
            return WebMode::PROXY;
        }

        public function form(): DynamicForm
        {
            return DynamicForm::make([DynamicField::make('build_assets')->checkbox()->default(false)]);
        }
    };
    $capability = new class(new ComponentCapability) extends AbstractComponentCapability
    {
        public static function id(): string
        {
            return 'tasks';
        }

        public static function label(): string
        {
            return 'Tasks';
        }
    };
    $version = app(GetBootstrap::class)->computeVersion();

    RegisterRuntimePreset::make($preset::id())->handler($preset::class)->register();
    RegisterComponentCapability::make($capability::id())->handler($capability::class)->register();

    $response = $this->actingAs($this->user)->getJson('/bootstrap')->assertSuccessful();
    $runtimes = collect($response->json('configs.application.runtimes'))->keyBy('id');
    $capabilities = collect($response->json('configs.application.capabilities'))->keyBy('id');

    expect($runtimes['python'])->toBe([
        'id' => 'python', 'label' => 'Python', 'versions' => [], 'web_mode' => 'proxy', 'form' => $preset->form()->toArray(),
    ])
        ->and($capabilities['tasks'])->toBe(['id' => 'tasks', 'label' => 'Tasks', 'description' => '', 'surfaces' => []])
        ->and($response->json('version'))->not->toBe($version)
        ->and((new Application(['runtime' => 'python']))->preset())->toBeInstanceOf($preset::class)
        ->and((new ComponentCapability(['capability' => 'tasks']))->handler())->toBeInstanceOf($capability::class);
});

test('registration can override labels and serialized forms', function () {
    $form = DynamicForm::make([DynamicField::make('command')->text()->default('run')]);

    RegisterRuntimePreset::make('custom')->handler(Custom::class)->label('Custom runtime')->form($form)->register();
    RegisterComponentCapability::make('worker')->handler(Worker::class)->label('Jobs')->form($form)->register();

    expect(config('component.runtimes.custom.label'))->toBe('Custom runtime')
        ->and(config('component.runtimes.custom.form'))->toBe($form->toArray())
        ->and(config('component.capabilities.worker.label'))->toBe('Jobs')
        ->and(config('component.capabilities.worker.form'))->toBe($form->toArray());
});

test('unknown and invalid runtime handlers fail explicitly', function (?string $handler) {
    config(['component.runtimes.unknown.handler' => $handler]);

    expect(fn () => (new Application(['runtime' => 'unknown']))->preset())->toThrow(InvalidArgumentException::class);
})->with([null, stdClass::class, 'MissingRuntimeClass']);

test('unknown and invalid capability handlers fail explicitly', function (?string $handler) {
    config(['component.capabilities.unknown.handler' => $handler]);

    expect(fn () => (new ComponentCapability(['capability' => 'unknown']))->handler())->toThrow(InvalidArgumentException::class);
})->with([null, stdClass::class, 'MissingCapabilityClass']);

test('registration rejects handlers outside the extension contracts', function () {
    expect(fn () => RegisterRuntimePreset::make('invalid')->handler(stdClass::class)->register())->toThrow(InvalidArgumentException::class)
        ->and(fn () => RegisterComponentCapability::make('invalid')->handler(stdClass::class)->register())->toThrow(InvalidArgumentException::class);
});

test('runtime and capability registries resolve dotted identifiers literally', function () {
    RegisterRuntimePreset::make('acme.custom')->handler(Custom::class)->register();
    RegisterComponentCapability::make('acme.worker')->handler(Worker::class)->register();

    expect((new Application(['runtime' => 'acme.custom']))->preset())->toBeInstanceOf(Custom::class)
        ->and((new ComponentCapability(['capability' => 'acme.worker']))->handler())->toBeInstanceOf(Worker::class);

    $configs = $this->actingAs($this->user)->getJson('/bootstrap')->assertSuccessful()->json('configs.application');

    expect(array_column($configs['runtimes'], 'id'))->toContain('acme.custom')
        ->and(array_column($configs['capabilities'], 'id'))->toContain('acme.worker');
});

test('registration rejects abstract runtime presets before changing the registry', function () {
    expect(fn () => RegisterRuntimePreset::make('invalid')->handler(AbstractRuntimePresetForRegistrationTest::class)->register())
        ->toThrow(InvalidArgumentException::class);

    expect(config('component.runtimes'))->not->toHaveKey('invalid');
});

test('registration rejects abstract component capabilities before changing the registry', function () {
    expect(fn () => RegisterComponentCapability::make('invalid')->handler(AbstractComponentCapabilityForRegistrationTest::class)->register())
        ->toThrow(InvalidArgumentException::class);

    expect(config('component.capabilities'))->not->toHaveKey('invalid');
});

abstract class AbstractRuntimePresetForRegistrationTest extends Custom {}

abstract class AbstractComponentCapabilityForRegistrationTest extends Worker {}
