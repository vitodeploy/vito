<?php

namespace App\SSH\Storage;

abstract class S3AbstractStorage extends AbstractStorage
{
    protected ?string $apiUrl = null;

    protected ?string $bucketRegion = null;

    public function getApiUrl(): string
    {
        return $this->apiUrl;
    }

    public function setApiUrl(?string $region = null): void
    {
        $this->bucketRegion = $region ?? $this->bucketRegion;
        $this->apiUrl = "https://s3.{$this->bucketRegion}.amazonaws.com";
    }

    // Getter and Setter for $bucketRegion
    public function getBucketRegion(): string
    {
        return $this->bucketRegion;
    }

    public function setBucketRegion(string $region): void
    {
        $this->bucketRegion = $region;
    }
}
