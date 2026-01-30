<?php

namespace App\Services\Local;

use App\Exceptions\SSHConnectionError;
use App\Helpers\LocalSocket;
use App\Services\AbstractService;
use Exception;

class VitoLocal extends AbstractService
{
    public static function id(): string
    {
        return 'vito-local';
    }

    public static function type(): string
    {
        return 'local';
    }

    public function unit(): string
    {
        return 'vito-local';
    }

    /**
     * @throws Exception
     */
    public function install(): void
    {
        throw new Exception('VitoLocal cannot be installed.');
    }

    /**
     * @throws Exception
     */
    public function uninstall(): void
    {
        throw new Exception('VitoLocal cannot be uninstalled.');
    }

    /**
     * @throws Exception
     */
    public function update(): void
    {
        $connection = $this->service->server->ssh();
        if ($connection instanceof LocalSocket) {
            $result = $connection->performUpdate();
            if ($result['status'] === 'failed') {
                throw new Exception($result['message'] ?? 'Update failed');
            }
        }
    }

    /**
     * @throws SSHConnectionError
     */
    public function version(): string
    {
        $connection = $this->service->server->ssh();
        if ($connection instanceof LocalSocket) {
            return $connection->getVersion();
        }

        return 'Unknown';
    }
}
