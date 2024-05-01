<?php

namespace App\SSH\Storage;

interface Storage
{
    public function upload(string $src, string $dest): array;

    public function download(string $src, string $dest): void;

    public function delete(string $path): void;
}
