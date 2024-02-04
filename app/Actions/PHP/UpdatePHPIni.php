<?php

namespace App\Actions\PHP;

use App\Models\Service;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class UpdatePHPIni
{
    /**
     * @throws ValidationException
     */
    public function update(Service $service, string $ini): void
    {
        $tmpName = Str::random(10).strtotime('now');
        try {
            /** @var \Illuminate\Filesystem\FilesystemAdapter $storageDisk */
            $storageDisk = Storage::disk('local');

            $storageDisk->put($tmpName, $ini);
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
}
