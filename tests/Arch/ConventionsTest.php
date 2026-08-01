<?php

use App\Events\SocketEvent;
use Illuminate\Support\Str;
use Tests\ArchTestCase;

/**
 * Models whose rows are reflected in GetBootstrap::configs(). In production the
 * bootstrap version is cached forever, so any write here has to bust it.
 *
 * @var array<string, string>
 */
const BOOTSTRAP_BACKED_MODELS = [
    'GithubApp' => 'Actions/GithubApp',
    'Plugin' => 'Actions/Plugins',
];

arch('broadcasting never happens from a read or decision layer')
    ->expect(SocketEvent::class)
    ->not->toBeUsedIn([
        'App\DTOs',
        'App\Enums',
        'App\Http\Resources',
        'App\Policies',
        'App\ValidationRules',
    ]);

arch('DTOs stay free of infrastructure')
    ->expect('App\DTOs')
    ->not->toUse([
        'App\Http\Controllers',
        'App\Jobs',
        'Illuminate\Support\Facades\DB',
        'Illuminate\Support\Facades\Http',
        'Inertia\Inertia',
    ]);

arch('listeners expose a handle method')
    ->expect('App\Listeners')
    ->classes()
    ->toHaveMethod('handle');

arch('console commands stay thin and delegate to actions')
    ->expect('App\Console\Commands')
    ->not->toUse(['App\Http\Controllers', 'Inertia\Inertia']);

it('busts the bootstrap version whenever bootstrap-backed state is written', function (): void {
    $offenders = [];

    foreach (BOOTSTRAP_BACKED_MODELS as $model => $namespace) {
        foreach (vitoArchFiles($namespace) as $file) {
            $contents = (string) file_get_contents($file->getRealPath());

            $writes = preg_match(
                '/\b'.$model.'::(?:[a-zA-Z_]+\([^;]{0,200}\)->)*(?:create|updateOrCreate|update|delete|forceDelete)\(/',
                $contents
            ) === 1 || Str::contains($contents, [
                'new '.$model.'(',
                '$'.lcfirst($model).'->save()',
                '$'.lcfirst($model).'->update([',
                '$'.lcfirst($model).'->delete()',
                '$'.lcfirst($model).'->forceDelete()',
            ]);

            if ($writes && ! Str::contains($contents, 'forgetVersion()')) {
                $offenders[] = $file->getRelativePathname();
            }
        }
    }

    $exempt = ArchTestCase::except('conventions.writes-without-bootstrap-bust');

    expect(array_values(array_diff($offenders, $exempt)))->toBe([]);
});
