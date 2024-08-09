<?php

namespace App\Helpers;

use FTP\Connection;

class FTP
{
    public function connect(string $host, string $port, bool $ssl = false): bool|Connection
    {
        if ($ssl) {
            return ftp_ssl_connect($host, $port, 5);
        }

        return ftp_connect($host, $port, 5);
    }

    public function login(string $username, string $password, bool|Connection $connection): bool
    {
        return ftp_login($connection, $username, $password);
    }

    public function close(bool|Connection $connection): void
    {
        ftp_close($connection);
    }

    public function passive(bool|Connection $connection, bool $passive): bool
    {
        return ftp_pasv($connection, $passive);
    }

    public function delete(bool|Connection $connection, string $path): bool
    {
        return ftp_delete($connection, $path);
    }
}
