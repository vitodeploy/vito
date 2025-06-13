<?php

namespace App\Actions\Database;

use App\Enums\DatabaseStatus;
use App\Models\Server;
use App\Models\Service;
use App\Services\Database\Database;

class SyncDatabases
{
    public function sync(Server $server): void
    {
        $service = $server->database();
        if (! $service instanceof Service) {
            return;
        }
        /** @var Database $handler */
        $handler = $service->handler();

        $this->updateCharsets($service, $handler);
        $this->updateDatabases($server, $handler);
    }

    private function updateCharsets(Service $service, Database $handler): void
    {
        $data = $service->type_data ?? [];
        $charsets = $handler->getCharsets();
        $data['charsets'] = $charsets['charsets'] ?? [];
        $data['defaultCharset'] = $charsets['defaultCharset'] ?? '';
        $service->type_data = $data;
        $service->save();
    }

    private function updateDatabases(Server $server, Database $handler): void
    {
        $databases = $handler->getDatabases();
        foreach ($databases as $database) {
            /** @var ?\App\Models\Database $db */
            $db = $server->databases()
                ->where('name', $database[0])
                ->first();

            if ($db === null) {
                $server->databases()->create([
                    'name' => $database[0],
                    'collation' => $database[2],
                    'charset' => $database[1],
                    'status' => DatabaseStatus::READY,
                ]);

                continue;
            }

            if ($db->collation !== $database[2] || $db->charset !== $database[1]) {
                $db->update([
                    'collation' => $database[2],
                    'charset' => $database[1],
                ]);
            }
        }
    }
}
