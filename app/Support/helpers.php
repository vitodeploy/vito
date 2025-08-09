<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

function generate_public_key(string $privateKeyPath, string $publicKeyPath): void
{
    chmod($privateKeyPath, 0400);
    exec("ssh-keygen -y -f {$privateKeyPath} > {$publicKeyPath}");
}

function generate_key_pair(string $path): void
{
    exec("ssh-keygen -t ed25519 -m PEM -N '' -f {$path}");
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

/**
 * Credit: https://gist.github.com/lorenzos/1711e81a9162320fde20
 */
function tail(string $filepath, int $lines = 1, bool $adaptive = true): string
{
    // Open file
    $f = @fopen($filepath, 'rb');
    if ($f === false) {
        return '';
    }

    // Sets buffer size, according to the number of lines to retrieve.
    // This gives a performance boost when reading a few lines from the file.
    if (! $adaptive) {
        $buffer = 4096;
    } else {
        $buffer = ($lines < 2 ? 64 : ($lines < 10 ? 512 : 4096));
    }

    // Jump to last character
    fseek($f, -1, SEEK_END);

    // Read it and adjust line number if necessary
    // (Otherwise the result would be wrong if file doesn't end with a blank line)
    if (fread($f, 1) != "\n") {
        $lines -= 1;
    }

    // Start reading
    $output = '';
    $chunk = '';

    // While we would like more
    while (ftell($f) > 0 && $lines >= 0) {
        // Figure out how far back we should jump
        $seek = min(ftell($f), $buffer);

        // Do the jump (backwards, relative to where we are)
        fseek($f, -$seek, SEEK_CUR);

        // Read a chunk and prepend it to our output
        $output = ($chunk = fread($f, $seek)).$output;

        // Jump back to where we started reading
        fseek($f, -mb_strlen($chunk !== false ? $chunk : '', '8bit'), SEEK_CUR);

        // Decrease our line counter
        $lines -= substr_count($chunk !== false ? $chunk : '', "\n");
    }

    // While we have too many lines
    // (Because of buffer size we might have read too many)
    while ($lines++ < 0) {
        // Find first newline and remove all text before that
        $output = substr($output, strpos($output, "\n") + 1);
    }

    // Close file and return
    fclose($f);

    return trim($output);
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
            continue; // Skip empty and current directory parts
        }
        if ($part === '..') {
            array_pop($absoluteParts); // Move up one directory
        } else {
            $absoluteParts[] = $part; // Add valid directory parts
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

function format_nginx_config(string $config): string
{
    $lines = explode("\n", trim($config));
    $indent = 0;
    $formattedLines = [];

    foreach ($lines as $line) {
        $trimmed = trim($line);

        // Preserve empty lines exactly as they are
        if ($trimmed === '') {
            $formattedLines[] = '';

            continue;
        }

        // If line is a closing brace, decrease indentation first
        if ($trimmed === '}') {
            $indent--;
        }

        // Apply indentation
        $formattedLines[] = str_repeat('    ', max(0, $indent)).$trimmed;

        // If line contains an opening brace, increase indentation
        if (str_ends_with($trimmed, '{')) {
            $indent++;
        }
    }

    return implode("\n", $formattedLines)."\n";
}

function user(): User
{
    /** @var User $user */
    $user = auth()->user();

    return $user;
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
    $paths = [
        '/usr/local/bin/php',
        '/usr/bin/php',
        '/opt/homebrew/bin/php',
        trim((string) shell_exec('which php')),
    ];

    return array_find($paths, fn ($path) => is_executable($path));
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
    // Remove any stale destination
    if (File::exists($to)) {
        File::deleteDirectory($to);
    }

    // Ensure parent of $to exists
    File::ensureDirectoryExists(dirname($to));

    // Copy + delete (works across mounts / volumes)
    if (! File::copyDirectory($from, $to)) {
        throw new RuntimeException("Could not copy [$from] to [$to]");
    }

    File::deleteDirectory($from);
}
