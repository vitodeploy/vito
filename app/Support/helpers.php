<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ProcessUtils;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\PhpExecutableFinder;

use function Illuminate\Support\php_binary;

function generate_public_key(string $privateKeyPath, string $publicKeyPath): void
{
    chmod($privateKeyPath, 0400);
    exec("ssh-keygen -y -f {$privateKeyPath} > {$publicKeyPath}");
}

/**
 * @throws RuntimeException
 */
function generate_key_pair(string $path): void
{
    exec("ssh-keygen -t ed25519 -m PEM -N '' -f ".escapeshellarg($path));

    if (! file_exists($path)) {
        exec("ssh-keygen -t ed25519 -N '' -f ".escapeshellarg($path).' 2>&1', $output, $exitCode);

        if ($exitCode !== 0 || ! file_exists($path)) {
            Log::error('Failed to generate SSH key pair', [
                'exit_code' => $exitCode,
                'output' => implode("\n", $output),
            ]);

            throw new RuntimeException('Failed to generate SSH key pair.');
        }
    }

    chmod($path, 0400);
}

/**
 * @throws Exception
 */
function date_with_timezone(mixed $date, string $timezone): string
{
    $dt = new DateTime('now', new DateTimeZone($timezone));
    $time = strtotime((string) $date);
    if ($time === false) {
        throw new Exception('Invalid date');
    }
    $dt->setTimestamp($time);

    return $dt->format('Y-m-d H:i:s');
}

function show_vito_version(): string
{
    $version = config('app.version');

    if (str($version)->contains('-beta')) {
        return str($version)->before('-beta')->toString();
    }

    return $version;
}

function convert_time_format(string $string): string
{
    $string = preg_replace('/(\d+)m/', '$1 minutes', $string);
    $string = preg_replace('/(\d+)s/', '$1 seconds', (string) $string);
    $string = preg_replace('/(\d+)d/', '$1 days', (string) $string);

    return (string) preg_replace('/(\d+)h/', '$1 hours', (string) $string);
}

function get_public_key_content(): string
{
    if (cache()->has('ssh_public_key_content')) {
        return cache()->get('ssh_public_key_content');
    }

    if (! file_exists(storage_path(config('core.ssh_public_key_name')))) {
        Artisan::call('ssh-key:generate --force');
    }

    $content = file_get_contents(storage_path(config('core.ssh_public_key_name')));

    if ($content === false) {
        return '';
    }

    $content = str($content)
        ->replace("\n", '')
        ->toString();

    cache()->put('ssh_public_key_content', $content, 60 * 60 * 24);

    return $content;
}

function tail(string $filepath, int $lines = 1, int $maxBytes = 1048576): string
{
    $f = @fopen($filepath, 'rb');
    if ($f === false) {
        return '';
    }

    $maxBytes = max(1, $maxBytes);
    fseek($f, -min($maxBytes, (int) fstat($f)['size']), SEEK_END);
    $content = (string) stream_get_contents($f, $maxBytes);
    fclose($f);

    return trim(implode("\n", array_slice(explode("\n", rtrim($content, "\n")), -max(1, $lines))));
}

/**
 * @param  array<array-key, mixed>  $variables
 * @throws ValidationException
 */
function substitute_variables(string $content, array $variables, int $limit = 1048576): string
{
    $replacements = [];
    $length = strlen($content);
    foreach ($variables as $variable => $value) {
        if (is_string($value) && $value !== '' && $value !== '0') {
            $placeholder = '${'.$variable.'}';
            $replacements[$placeholder] = $value;
            $length += substr_count($content, $placeholder) * max(0, strlen($value) - strlen($placeholder));
        }
    }

    if ($length > $limit) {
        throw ValidationException::withMessages(['variables' => 'The content is too large once its variables are filled in.']);
    }

    return strtr($content, $replacements);
}

function get_from_route(string $modelName, string $routeKey): mixed
{
    $model = request()->route($routeKey);

    if (! $model) {
        $model = Route::getRoutes()->match(Request::create(url()->previous()))->parameter($routeKey);
    }

    if ($model instanceof $modelName) {
        return $model;
    }

    if ($model) {
        return $modelName::query()->find($model);
    }

    return null;
}

function absolute_path(string $path): string
{
    $parts = explode('/', $path);
    $absoluteParts = [];

    foreach ($parts as $part) {
        if ($part === '' || $part === '.') {
            continue;
        }
        if ($part === '..') {
            array_pop($absoluteParts);
        } else {
            $absoluteParts[] = $part;
        }
    }

    return '/'.implode('/', $absoluteParts);
}

function home_path(string $user): string
{
    if ($user === 'root') {
        return '/root';
    }

    return '/home/'.$user;
}

function format_webserver_config(string $config): string
{
    $lines = explode("\n", trim($config));
    $indent = 0;
    $formattedLines = [];
    $lastWasEmpty = false;

    foreach ($lines as $line) {
        $trimmed = trim($line);

        if ($trimmed === '') {
            if (! $lastWasEmpty) {
                $formattedLines[] = '';
                $lastWasEmpty = true;
            }

            continue;
        }

        $lastWasEmpty = false;

        if ($trimmed === '}') {
            $indent--;
            if (end($formattedLines) === '') {
                array_pop($formattedLines);
            }
        }

        $formattedLines[] = str_repeat('    ', min(max(0, $indent), 32)).$trimmed;

        if (str_ends_with($trimmed, '{')) {
            $indent++;
        }
    }

    while (! empty($formattedLines) && $formattedLines[0] === '') {
        array_shift($formattedLines);
    }
    while (! empty($formattedLines) && end($formattedLines) === '') {
        array_pop($formattedLines);
    }

    return implode("\n", $formattedLines)."\n";
}

function user(): User
{
    /** @var User $user */
    $user = Auth::user();

    return $user;
}

function app_url(string $path): string
{
    return rtrim((string) config('app.url'), '/').'/'.ltrim($path, '/');
}

function plain_text_response(?string $content): Response
{
    return response($content ?? '', 200, [
        'Content-Type' => 'text/plain; charset=UTF-8',
        'X-Content-Type-Options' => 'nosniff',
    ]);
}

function plugins_path(?string $path = null): string
{
    if ($path === null) {
        $path = storage_path('plugins');
        if (! file_exists($path)) {
            mkdir($path, 0755, true);
        }

        return $path;
    }

    return storage_path('plugins'.'/'.$path);
}

function composer_path(): ?string
{
    $paths = [
        '/usr/local/bin/composer',
        '/usr/bin/composer',
        '/opt/homebrew/bin/composer',
        trim((string) shell_exec('which composer')),
    ];

    return array_find($paths, fn ($path) => is_executable($path));
}

function php_path(): ?string
{
    $phpBinary = function_exists('Illuminate\Support\php_binary')
        ? php_binary()
        : (new PhpExecutableFinder)->find(false);

    return $phpBinary !== false
        ? ProcessUtils::escapeArgument($phpBinary)
        : null;
}

function git_path(): ?string
{
    $paths = [
        '/usr/local/bin/git',
        '/usr/bin/git',
        '/opt/homebrew/bin/git',
        trim((string) shell_exec('which git')),
    ];

    return array_find($paths, fn ($path) => is_executable($path));
}

function move_directory(string $from, string $to): void
{
    if (File::exists($to)) {
        File::deleteDirectory($to);
    }

    File::ensureDirectoryExists(dirname($to));

    if (! File::copyDirectory($from, $to)) {
        throw new RuntimeException("Could not copy [$from] to [$to]");
    }

    File::deleteDirectory($from);
}

function is_self_hosted(): bool
{
    return config('app.self_hosted', true);
}
