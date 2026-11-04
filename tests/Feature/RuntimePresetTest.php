<?php

use App\Actions\Bootstrap\GetBootstrap;
use App\ComponentCapabilities\AbstractComponentCapability;
use App\ComponentCapabilities\Worker;
use App\DTOs\DynamicField;
use App\DTOs\DynamicForm;
use App\Enums\WebMode;
use App\Models\Application;
use App\Models\ComponentCapability;
use App\Models\Environment;
use App\Plugins\RegisterComponentCapability;
use App\Plugins\RegisterRuntimePreset;
use App\RuntimePresets\AbstractRuntimePreset;
use App\RuntimePresets\Bun;
use App\RuntimePresets\Custom;
use App\RuntimePresets\Go;
use App\RuntimePresets\Laravel;
use App\RuntimePresets\NodeJS;
use App\RuntimePresets\PHP;
use App\RuntimePresets\Pnpm;
use App\RuntimePresets\Python;
use App\RuntimePresets\Rust;
use App\RuntimePresets\StaticSite;
use App\RuntimePresets\Yarn;
use App\Support\RepositoryFiles;
use App\Tooling\ToolingRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

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
        ->and($descriptor)->toMatchArray([
            'id' => $id,
            'label' => $preset::label(),
            'versions' => $preset->versions(),
            'web_mode' => $mode->getText(),
            'form' => $preset->form()->toArray(),
            'creation_form' => $preset->creationForm()->toArray(),
            'suggests_database' => $preset->suggestsDatabase(),
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
    ['pnpm', Pnpm::class, WebMode::PROXY, 'node'],
    ['yarn', Yarn::class, WebMode::PROXY, 'node'],
    ['bun', Bun::class, WebMode::PROXY, 'bun'],
    ['go', Go::class, WebMode::PROXY, 'go'],
    ['rust', Rust::class, WebMode::PROXY, 'rust'],
    ['python', Python::class, WebMode::PROXY, 'python'],
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
    ['pnpm', []],
    ['yarn', []],
    ['bun', []],
    ['go', []],
    ['rust', []],
    ['python', []],
    ['static', []],
    ['custom', []],
]);

test('suggested deployment scripts are loaded by stage without reusing site scripts', function () {
    expect((new Laravel)->defaultScripts())->toBe([
        'build' => "composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader\nif [ -f package.json ]; then\n  if [ -f package-lock.json ] || [ -f npm-shrinkwrap.json ]; then\n    npm ci\n  else\n    npm install\n  fi\n  npm run build --if-present\nfi\n",
        'pre-release' => "php artisan migrate --force\n",
        'post-release' => "php artisan optimize\n",
    ])
        ->and((new PHP)->defaultScripts()['build'])->toContain('if [ -f composer.json ]; then', 'composer install')
        ->and((new NodeJS)->defaultScripts())->toBe([
            'build' => "if [ -f package-lock.json ] || [ -f npm-shrinkwrap.json ]; then\n  npm ci\nelse\n  npm install\nfi\nnpm run build --if-present\n",
            'pre-release' => '',
            'post-release' => '',
        ])
        ->and((new Bun)->defaultScripts()['build'])->toContain('bun install')
        ->and((new Pnpm)->defaultScripts())->toBe([
            'build' => "if [ -f pnpm-lock.yaml ]; then\n  pnpm install --frozen-lockfile --prod=false\nelse\n  pnpm install --prod=false\nfi\npnpm run --if-present build\n",
            'pre-release' => '',
            'post-release' => '',
        ])
        ->and((new Yarn)->defaultScripts()['build'])->toContain(
            'yarn set version',
            '--yarn-path --only-if-needed',
            "elif [ -f yarn.lock ] && grep -q '^  version: 6$' yarn.lock; then\n  YARN_IGNORE_PATH=1 yarn set version 3.x --yarn-path --only-if-needed\n",
            'yarn install --frozen-lockfile --production=false',
            "if [ -f yarn.lock ]; then\n      yarn install --immutable\n    else\n      yarn install --no-immutable\n    fi",
            'yarn run build',
        )
        ->and((new Yarn)->defaultScripts()['pre-release'])->toBe('')
        ->and((new Yarn)->defaultScripts()['post-release'])->toBe('');
});

test('compiled and python runtimes seed release-local build scripts', function (string $runtime, string $build) {
    $preset = (new Application(['runtime' => $runtime]))->preset();

    expect($preset->defaultScripts())->toBe(['build' => $build, 'pre-release' => '', 'post-release' => ''])
        ->and($preset->sharedPaths())->toBe([]);
})->with([
    ['go', "go mod download\ngo build -o app .\n"],
    ['rust', "if [ -f Cargo.lock ]; then\n  cargo build --release --locked\nelse\n  cargo build --release\nfi\n"],
    ['python', "python -m venv .venv\nif [ -f requirements.txt ]; then\n  .venv/bin/python -m pip install -r requirements.txt\nelif [ -f pyproject.toml ] || [ -f setup.py ]; then\n  .venv/bin/python -m pip install .\nfi\n"],
]);

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
        ->and((new Pnpm)->startCommand())->toBe('pnpm start')
        ->and((new Yarn)->startCommand())->toBe('yarn start')
        ->and((new Bun)->startCommand())->toBe('bun run start')
        ->and((new Go)->startCommand())->toBe('./app')
        ->and((new Rust)->startCommand())->toBe('./target/release/app')
        ->and((new Python)->startCommand())->toBe('.venv/bin/python main.py');
});

test('plugins can register runtime presets and capabilities in bootstrap', function () {
    $preset = new class extends AbstractRuntimePreset
    {
        public static function id(): string
        {
            return 'plugin-runtime';
        }

        public static function label(): string
        {
            return 'Plugin runtime';
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

    expect($runtimes['plugin-runtime'])->toMatchArray([
        'id' => 'plugin-runtime', 'label' => 'Plugin runtime', 'versions' => [], 'web_mode' => 'proxy', 'form' => $preset->form()->toArray(),
        'creation_form' => [], 'required_services' => ['webserver', 'process_manager'], 'version_service' => null, 'suggests_database' => false,
    ])
        ->and($capabilities['tasks'])->toBe(['id' => 'tasks', 'label' => 'Tasks', 'description' => '', 'surfaces' => []])
        ->and($response->json('version'))->not->toBe($version)
        ->and((new Application(['runtime' => 'plugin-runtime']))->preset())->toBeInstanceOf($preset::class)
        ->and((new ComponentCapability(['capability' => 'tasks']))->handler())->toBeInstanceOf($capability::class);
});

test('runtime detection recognizes repositories and narrows versions to their constraints', function (string $runtime, array $files, ?array $versions) {
    $detected = (new Application(['runtime' => $runtime]))->preset()->detect(new RepositoryFiles(fn (string $path): ?string => $files[$path] ?? null));

    expect($detected)->toBe($versions);
})->with([
    'laravel with caret constraint' => ['laravel', ['composer.json' => '{"require":{"php":"^8.3","laravel/framework":"^13.0"}}'], ['8.5', '8.4', '8.3']],
    'laravel ignores plain php' => ['laravel', ['composer.json' => '{"require":{"php":"^8.3"}}'], null],
    'php alternatives' => ['php', ['composer.json' => '{"require":{"php":"^7.4 || ^8.4"}}'], ['8.5', '8.4', '7.4']],
    'php bounded range' => ['php', ['composer.json' => '{"require":{"php":">=8.1,<8.3"}}'], ['8.2', '8.1']],
    'php constraint with leading whitespace' => ['php', ['composer.json' => json_encode(['require' => ['php' => str_repeat(' ', 300).'^8.4']])], ['8.5', '8.4']],
    'php tilde patch' => ['php', ['composer.json' => '{"require":{"php":"~8.2.0"}}'], ['8.2']],
    'php spaced comparator' => ['php', ['composer.json' => '{"require":{"php":">= 8.4"}}'], ['8.5', '8.4']],
    'php unsatisfiable keeps every version' => ['php', ['composer.json' => '{"require":{"php":"^9.0"}}'], ['8.5', '8.4', '8.3', '8.2', '8.1', '8.0', '7.4', '7.3', '7.2', '7.1', '7.0', '5.6']],
    'php from index file' => ['php', ['index.php' => '<?php'], ['8.5', '8.4', '8.3', '8.2', '8.1', '8.0', '7.4', '7.3', '7.2', '7.1', '7.0', '5.6']],
    'php requires a php file' => ['php', ['index.html' => '<html>'], null],
    'node engines' => ['node', ['package.json' => '{"engines":{"node":">=23"}}'], ['24', '23']],
    'node nvmrc wins over engines' => ['node', ['package.json' => '{"engines":{"node":">=23"}}', '.nvmrc' => "v22.11.0\n"], ['22']],
    'node empty nvmrc falls back to engines' => ['node', ['package.json' => '{"engines":{"node":">=23"}}', '.nvmrc' => "\n"], ['24', '23']],
    'node unparsable alias keeps every version' => ['node', ['package.json' => '{}', '.nvmrc' => 'lts/*'], ['24', '23', '22']],
    'node skips php repositories' => ['node', ['package.json' => '{}', 'composer.json' => '{}'], null],
    'pnpm lockfile uses node engines' => ['pnpm', ['package.json' => '{"engines":{"node":">=23"}}', 'pnpm-lock.yaml' => 'lockfileVersion: 9.0'], ['24', '23']],
    'pnpm package manager without a lockfile' => ['pnpm', ['package.json' => '{"packageManager":"pnpm@10.0.0"}', '.nvmrc' => '22.11.0'], ['22']],
    'pnpm skips other package managers' => ['pnpm', ['package.json' => '{"packageManager":"yarn@4.9.2"}', 'pnpm-lock.yaml' => ''], null],
    'pnpm requires a manifest' => ['pnpm', ['pnpm-lock.yaml' => ''], null],
    'pnpm requires a hint' => ['pnpm', ['package.json' => '{}'], null],
    'pnpm skips php repositories' => ['pnpm', ['package.json' => '{}', 'composer.json' => '{}', 'pnpm-lock.yaml' => ''], null],
    'pnpm ignores invalid metadata' => ['pnpm', ['package.json' => '{"packageManager":[]}', 'pnpm-lock.yaml' => ''], ['24', '23', '22']],
    'yarn lockfile uses node engines' => ['yarn', ['package.json' => '{"engines":{"node":">=23"}}', 'yarn.lock' => '# yarn lockfile v1'], ['24', '23']],
    'yarn package manager without a lockfile' => ['yarn', ['package.json' => '{"packageManager":"yarn@4.9.2"}', '.node-version' => '22.11.0'], ['22']],
    'yarn skips other package managers' => ['yarn', ['package.json' => '{"packageManager":"pnpm@10.0.0"}', 'yarn.lock' => ''], null],
    'yarn requires a manifest' => ['yarn', ['yarn.lock' => ''], null],
    'yarn requires a hint' => ['yarn', ['package.json' => '{}'], null],
    'yarn skips php repositories' => ['yarn', ['package.json' => '{}', 'composer.json' => '{}', 'yarn.lock' => ''], null],
    'yarn ignores empty metadata' => ['yarn', ['package.json' => '{"packageManager":""}', 'yarn.lock' => ''], ['24', '23', '22']],
    'bun lockfile' => ['bun', ['bun.lock' => '{}', 'package.json' => '{}'], (new Bun)->versions()],
    'go minimum version' => ['go', ['go.mod' => "module example.com/app\n\ngo 1.26.0\n"], ['1.27', '1.26']],
    'go pinned version wins' => ['go', ['go.mod' => "module example.com/app\ngo 1.25", '.go-version' => "1.26.3\n"], ['1.26']],
    'go invalid hint keeps choices' => ['go', ['go.mod' => 'module example.com/app', '.go-version' => 'latest'], ['1.27', '1.26', '1.25']],
    'go requires a manifest' => ['go', ['.go-version' => '1.27'], null],
    'go skips php repositories' => ['go', ['go.mod' => '', 'composer.json' => '{}'], null],
    'rust manifest' => ['rust', ['Cargo.toml' => '[package]'], ['1.99', '1.98', '1.97']],
    'rust pinned toolchain' => ['rust', ['Cargo.toml' => '[package]', 'rust-toolchain' => "1.98.0\n"], ['1.98']],
    'rust legacy filename with toml' => ['rust', ['Cargo.toml' => '[package]', 'rust-toolchain' => "[toolchain]\nchannel = \"1.98.0\"\n"], ['1.98']],
    'rust toml filename' => ['rust', ['Cargo.toml' => '[package]', 'rust-toolchain.toml' => "[toolchain]\nchannel = \"1.98.0\"\n"], ['1.98']],
    'rust legacy file takes precedence' => ['rust', ['Cargo.toml' => '[package]', 'rust-toolchain' => '1.97.1', 'rust-toolchain.toml' => "[toolchain]\nchannel = \"1.98.0\""], ['1.97']],
    'rust toml literal with comments' => ['rust', ['Cargo.toml' => '[package]', 'rust-toolchain.toml' => "[toolchain] # pinned\r\n  channel = '1.98.0' # stable release\r\n"], ['1.98']],
    'rust ignores other tables' => ['rust', ['Cargo.toml' => '[package]', 'rust-toolchain.toml' => "[toolchain]\nprofile = \"minimal\"\n[other]\nchannel = \"1.98.0\""], ['1.99', '1.98', '1.97']],
    'rust invalid toml keeps choices' => ['rust', ['Cargo.toml' => '[package]', 'rust-toolchain.toml' => "[toolchain]\nchannel = \"1.98.0"], ['1.99', '1.98', '1.97']],
    'rust toml stable keeps choices' => ['rust', ['Cargo.toml' => '[package]', 'rust-toolchain.toml' => "[toolchain]\nchannel = \"stable\""], ['1.99', '1.98', '1.97']],
    'rust stable keeps choices' => ['rust', ['Cargo.toml' => '[package]', 'rust-toolchain' => 'stable'], ['1.99', '1.98', '1.97']],
    'rust requires a manifest' => ['rust', ['rust-toolchain' => '1.99'], null],
    'rust skips php repositories' => ['rust', ['Cargo.toml' => '', 'composer.json' => '{}'], null],
    'python requirements' => ['python', ['requirements.txt' => 'gunicorn'], ['3.14', '3.13', '3.12', '3.11']],
    'python pyproject with version' => ['python', ['pyproject.toml' => '[project]', '.python-version' => "3.12.4\n"], ['3.12']],
    'python project bounded range' => ['python', ['pyproject.toml' => "[project]\nrequires-python = \">=3.12,<3.14\""], ['3.13', '3.12']],
    'python project compatible release' => ['python', ['pyproject.toml' => "[project]\nrequires-python = \"~=3.12.0\""], ['3.12']],
    'python project compatible minor' => ['python', ['pyproject.toml' => "[project]\nrequires-python = \"~= 3.12\""], ['3.14', '3.13', '3.12']],
    'python project wildcard equality' => ['python', ['pyproject.toml' => "[project]\nrequires-python = \"==3.12.*\""], ['3.12']],
    'python project excluded minor' => ['python', ['pyproject.toml' => "[project]\nrequires-python = \">= 3.12, != 3.14.*\""], ['3.13', '3.12']],
    'python exact patch exclusion keeps the minor series' => ['python', ['pyproject.toml' => "[project]\nrequires-python = \">=3.13,<3.14,!=3.13.0\""], ['3.13']],
    'python patch wildcard exclusion keeps later patches' => ['python', ['pyproject.toml' => "[project]\nrequires-python = \">=3.13,<3.14,!=3.13.0.*\""], ['3.13']],
    'python ignores hints inside multiline basic strings' => ['python', ['pyproject.toml' => "[project]\nreadme = { text = \"\"\"\nrequires-python = \"<3.14\"\n\"\"\", content-type = \"text/plain\" }\nrequires-python = \">=3.14\""], ['3.14']],
    'python ignores hints inside multiline literal strings' => ['python', ['pyproject.toml' => "[project]\nreadme = { text = '''\nrequires-python = \"<3.14\"\n''', content-type = \"text/plain\" }\nrequires-python = \">=3.14\""], ['3.14']],
    'python ignores table headers inside multiline strings' => ['python', ['pyproject.toml' => "[tool.example]\ntemplate = \"\"\"\n[project]\nrequires-python = \"<3.14\"\n\"\"\"\n[project]\nrequires-python = \">=3.14\""], ['3.14']],
    'python ignores string delimiters inside comments and literal strings' => ['python', ['pyproject.toml' => "# \"\"\"\n[project]\ndescription = '\"\"\"'\nrequires-python = \">=3.14\""], ['3.14']],
    'python explicit pin wins over project range' => ['python', ['pyproject.toml' => "[project]\nrequires-python = \">=3.12,<3.14\"", '.python-version' => '3.12.4'], ['3.12']],
    'python invalid pin still keeps choices' => ['python', ['pyproject.toml' => "[project]\nrequires-python = \">=3.12,<3.14\"", '.python-version' => 'latest'], ['3.14', '3.13', '3.12', '3.11']],
    'python empty pin still keeps choices' => ['python', ['pyproject.toml' => "[project]\nrequires-python = \">=3.12,<3.14\"", '.python-version' => ''], ['3.14', '3.13', '3.12', '3.11']],
    'python literal range with comments' => ['python', ['pyproject.toml' => "[project] # metadata\r\n  requires-python = '>=3.12,<3.14' # supported\r\n"], ['3.13', '3.12']],
    'python ignores commented hints' => ['python', ['pyproject.toml' => "[project]\n# requires-python = \"<3.14\""], ['3.14', '3.13', '3.12', '3.11']],
    'python ignores other tables' => ['python', ['pyproject.toml' => "[project]\nname = \"app\"\n[tool.other]\nrequires-python = \"<3.14\""], ['3.14', '3.13', '3.12', '3.11']],
    'python invalid project range keeps choices' => ['python', ['pyproject.toml' => "[project]\nrequires-python = \"not-a-version\""], ['3.14', '3.13', '3.12', '3.11']],
    'python malformed numeric suffix keeps choices' => ['python', ['pyproject.toml' => "[project]\nrequires-python = \"~=3.12oops\""], ['3.14', '3.13', '3.12', '3.11']],
    'python prerelease constraint' => ['python', ['pyproject.toml' => "[project]\nrequires-python = \">=3.13.0rc1\""], ['3.14', '3.13']],
    'php stability suffix' => ['php', ['composer.json' => '{"require":{"php":"^8.4@dev"}}'], ['8.5', '8.4']],
    'node wildcard version' => ['node', ['package.json' => '{"engines":{"node":"24.x"}}'], ['24']],
    'node repeated x wildcard' => ['node', ['package.json' => '{"engines":{"node":"22.x.x"}}'], ['22']],
    'node repeated star wildcard' => ['node', ['package.json' => '{"engines":{"node":"22.*.*"}}'], ['22']],
    'node prerelease constraint' => ['node', ['package.json' => '{"engines":{"node":">=23.0.0-rc.1"}}'], ['24', '23']],
    'node combined prerelease metadata' => ['node', ['package.json' => '{"engines":{"node":">=23.0.0-rc.1+build.1"}}'], ['24', '23']],
    'php combined stability suffix' => ['php', ['composer.json' => '{"require":{"php":"^8.4.0-beta1@dev"}}'], ['8.5', '8.4']],
    'python unsupported project range keeps choices' => ['python', ['pyproject.toml' => "[project]\nrequires-python = \">=4.0\""], ['3.14', '3.13', '3.12', '3.11']],
    'python setup' => ['python', ['setup.py' => 'from setuptools import setup'], ['3.14', '3.13', '3.12', '3.11']],
    'python invalid hint keeps choices' => ['python', ['requirements.txt' => '', '.python-version' => 'not-a-version'], ['3.14', '3.13', '3.12', '3.11']],
    'python requires a manifest' => ['python', ['.python-version' => '3.14'], null],
    'python skips php repositories' => ['python', ['requirements.txt' => '', 'composer.json' => '{}'], null],
    'static index' => ['static', ['index.html' => '<html>'], []],
    'static skips built projects' => ['static', ['index.html' => '<html>', 'package.json' => '{}'], null],
    'custom never detects' => ['custom', ['composer.json' => '{}'], null],
]);

test('caret constraints stay within the minor for 0.x versions', function () {
    $preset = new class extends Custom
    {
        public function versions(): array
        {
            return ['1.0', '0.3', '0.2', '0.0.3', '0.0.2'];
        }

        public function within(string $constraint): array
        {
            return $this->compatibleVersions($constraint);
        }
    };

    expect($preset->within('^0.2'))->toBe(['0.2'])
        ->and($preset->within('^0.0.2'))->toBe(['0.0.2'])
        ->and($preset->within('^1.0'))->toBe(['1.0']);
});

test('the version service is the required service that takes a version', function () {
    $preset = new class extends Custom
    {
        public static function id(): string
        {
            return 'mixed';
        }

        public function versions(): array
        {
            return ['1'];
        }

        public function requiredServices(string $version): array
        {
            return ['redis' => null, 'php' => $version];
        }
    };
    RegisterRuntimePreset::make($preset::id())->handler($preset::class)->register();

    $runtimes = $this->actingAs($this->user)->getJson('/bootstrap')->assertSuccessful()->json('configs.application.runtimes');

    expect(collect($runtimes)->firstWhere('id', 'mixed')['version_service'])->toBe('php');
});

test('repository files ignore oversized files and invalid json', function () {
    $reads = 0;
    $files = new RepositoryFiles(function (string $path) use (&$reads): ?string {
        $reads++;

        return ['big.json' => str_repeat('a', 1024 * 1024 + 1), 'bad.json' => '{not json'][$path] ?? null;
    });

    expect($files->has('big.json'))->toBeFalse()
        ->and($files->json('bad.json'))->toBe([])
        ->and($files->has('bad.json'))->toBeTrue()
        ->and($reads)->toBe(2);
});

test('laravel seeds environment variables and validates a pasted key', function () {
    $environment = new Environment;
    $environment->slug = 'staging';
    $generated = (new Laravel)->defaultVariables($environment, []);
    $key = 'base64:'.base64_encode(str_repeat('a', 32));
    $pasted = (new Laravel)->defaultVariables($environment, ['app_key' => $key]);

    expect(strlen(base64_decode(substr($generated['APP_KEY']['value'], 7), true)))->toBe(32)
        ->and($generated['APP_KEY']['is_secret'])->toBeTrue()
        ->and($generated['APP_ENV'])->toBe(['value' => 'staging', 'is_secret' => false])
        ->and($generated['APP_DEBUG']['value'])->toBe('false')
        ->and($pasted['APP_KEY']['value'])->toBe($key)
        ->and((new PHP)->defaultVariables($environment, []))->toBe([]);
});

test('a pasted laravel key must be one laravel can use', function (mixed $key, bool $valid) {
    expect(Validator::make(['app_key' => $key], (new Laravel)->creationRules())->passes())->toBe($valid);
})->with([
    'aes-256 base64' => ['base64:'.base64_encode(str_repeat('a', 32)), true],
    'aes-128 base64' => ['base64:'.base64_encode(str_repeat('a', 16)), true],
    'raw 32 characters' => [str_repeat('a', 32), true],
    'truncated base64' => ['base64:abc=', false],
    'not base64' => ['base64:!!!not-base64!!!', false],
    'wrong raw length' => ['not-a-key', false],
    'raw with control characters' => [str_repeat('a', 31)."\n", false],
    'raw with a space' => [str_repeat('a', 31).' ', false],
    'array' => [['a'], false],
]);

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
