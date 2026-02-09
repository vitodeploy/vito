<?php

namespace App\Helpers;

use phpseclib3\Net\SFTP as PhpseclibSFTP;
use Throwable;

class SFTP
{
    public function connect(string $host, int $port, string $username, string $password): bool
    {
        try {
            $sftp = new PhpseclibSFTP($host, $port);

            return $sftp->login($username, $password);
        } catch (Throwable) {
            return false;
        }
    }
}
