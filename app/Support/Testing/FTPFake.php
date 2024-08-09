<?php

namespace App\Support\Testing;

use FTP\Connection;
use PHPUnit\Framework\Assert;

class FTPFake
{
    protected array $connections = [];

    protected array $logins = [];

    public function connect(string $host, string $port, bool $ssl = false): bool|Connection
    {
        $this->connections[] = compact('host', 'port', 'ssl');

        return true;
    }

    public function login(string $username, string $password, bool|Connection $connection): bool
    {
        $this->logins[] = compact('username', 'password');

        return true;
    }

    public function close(bool|Connection $connection): void
    {
        //
    }

    public function passive(bool|Connection $connection, bool $passive): void
    {
        //
    }

    public function delete(bool|Connection $connection, string $path): void
    {
        //
    }

    public function assertConnected(string $host): void
    {
        if (! $this->connections) {
            Assert::fail('No connections are made');
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
        Assert::assertTrue(true, $connected);
    }
}
