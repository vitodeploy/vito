<?php

namespace App\Actions\PHP;

use App\Models\Server;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class UpdatePHPIni
{
    /**
     * @throws ValidationException
     */
    public function update(Server $server, array $input): void
    {
        $this->validate($server, $input);

        $service = $server->php($input['version']);

        $tmpName = Str::random(10).strtotime('now');
        try {
            /** @var \Illuminate\Filesystem\FilesystemAdapter $storageDisk */
            $storageDisk = Storage::disk('local');

            $storageDisk->put($tmpName, $input['ini']);
            $service->server->ssh('root')->upload(
                $storageDisk->path($tmpName),
                "/etc/php/$service->version/cli/php.ini"
            );
            $this->deleteTempFile($tmpName);
        } catch (Throwable) {
            $this->deleteTempFile($tmpName);
            throw ValidationException::withMessages([
                'ini' => __("Couldn't update php.ini file!"),
            ]);
        }

        $service->restart();
    }

    private function deleteTempFile(string $name): void
    {
        if (Storage::disk('local')->exists($name)) {
            Storage::disk('local')->delete($name);
        }
    }

    public function validate(Server $server, array $input): void
    {
        Validator::make($input, [
            'ini' => [
                'required',
                'string',
            ],
            'version' => 'required|string',
        ])->validate();

        if (! in_array($input['version'], $server->installedPHPVersions())) {
            throw ValidationException::withMessages(
                ['version' => __('This version is not installed')]
            );
        }
    }
}
