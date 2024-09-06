<?php

namespace App\StorageProviders;

use Aws\S3\S3Client;

interface S3ClientInterface
{
    public function getClient(): S3Client;
}
