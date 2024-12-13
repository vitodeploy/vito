<?php

use App\Exceptions\SSHError;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

function generate_public_key($privateKeyPath, $publicKeyPath): void
{
    chmod($privateKeyPath, 0400);
    exec("ssh-keygen -y -f {$privateKeyPath} > {$publicKeyPath}");
}

function generate_key_pair($path): void
{
    exec("ssh-keygen -t ed25519 -m PEM -N '' -f {$path}");
    chmod($path, 0400);
}

/**
 * @throws Exception
 */
function date_with_timezone($date, $timezone): string
{
    $dt = new DateTime('now', new DateTimeZone($timezone));
    $dt->setTimestamp(strtotime($date));

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

function convert_time_format($string): string
{
    $string = preg_replace('/(\d+)m/', '$1 minutes', $string);
    $string = preg_replace('/(\d+)s/', '$1 seconds', $string);
    $string = preg_replace('/(\d+)d/', '$1 days', $string);

    return preg_replace('/(\d+)h/', '$1 hours', $string);
}

function get_public_key_content(): string
{
    if (! file_exists(storage_path(config('core.ssh_public_key_name')))) {
        Artisan::call('ssh-key:generate --force');
    }

    return str(file_get_contents(storage_path(config('core.ssh_public_key_name'))))
        ->replace("\n", '')
        ->toString();
}

function run_action(object $static, Closure $callback): void
{
    try {
        $callback();
    } catch (SSHError $e) {
        Notification::make()
            ->danger()
            ->title($e->getMessage())
            ->body($e->getLog()?->getContent(30))
            ->actions([
                Action::make('View Logs')
                    ->url(App\Web\Pages\Servers\Logs\Index::getUrl([
                        'server' => $e->getLog()?->server_id,
                    ]))
                    ->openUrlInNewTab(),
            ])
            ->send();

        if (method_exists($static, 'halt')) {
            $reflectionMethod = new ReflectionMethod($static, 'halt');
            $reflectionMethod->invoke($static);
        }
    } catch (ValidationException $e) {
        Notification::make()
            ->danger()
            ->title($e->getMessage())
            ->send();

        if (method_exists($static, 'halt')) {
            $reflectionMethod = new ReflectionMethod($static, 'halt');
            $reflectionMethod->invoke($static);
        }
    }
}

/**
 * Credit: https://gist.github.com/lorenzos/1711e81a9162320fde20
 */
function tail($filepath, $lines = 1, $adaptive = true): string
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
        fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);

        // Decrease our line counter
        $lines -= substr_count($chunk, "\n");
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
