<?php

namespace App\Support\Testing;

use PHPUnit\Framework\Assert;

class SFTPFake
{
    /**
     * @var array<array{host: string, port: int, username: string}>
     */
    protected array $connections = [];

    public function connect(string $host, int $port, string $username, string $password): bool
    {
        $this->connections[] = ['host' => $host, 'port' => $port, 'username' => $username];

        return true;
    }

    public function assertConnected(string $host): void
    {
        if ($this->connections === []) {
            Assert::fail('No SFTP connections are made');
        }
        $connected = false;
        foreach ($this->connections as $connection) {
            if ($connection['host'] === $host) {
                $connected = true;
                break;
            }
        }
        if (! $connected) {
            Assert::fail('The expected host is not connected');
        }
    }
}
