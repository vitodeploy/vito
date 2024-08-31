<?php

namespace App\StorageProviders;

interface S3StorageInterface
{
    public function getApiUrl(): string;

    public function setApiUrl(?string $region = null): void;

    public function getBucketRegion(): string;

    public function setBucketRegion(string $region): void;
}
