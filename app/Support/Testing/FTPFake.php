<?php

namespace App\Support\Testing;

use FTP\Connection;
use PHPUnit\Framework\Assert;

class FTPFake
{
    /**
     * @var array<array{host: string, port: string, ssl: bool}>
     */
    protected array $connections = [];

    /**
     * @var array<array{username: string, password: string}>
     */
    protected array $logins = [];

    public function connect(string $host, string $port, bool $ssl = false): bool|Connection
    {
        $this->connections[] = ['host' => $host, 'port' => $port, 'ssl' => $ssl];

        return true;
    }

    public function login(string $username, string $password, bool|Connection $connection): bool
    {
        $this->logins[] = ['username' => $username, 'password' => $password];

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
        if ($this->connections === []) {
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
    }
}
