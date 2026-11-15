<?php

namespace App\StorageProviders;

use App\Helpers\SSH;
use App\Models\BackupFile;
use App\Models\StorageProvider;
use App\StorageProviders\StorageProvider as StorageProviderContract;
use Closure;
use Illuminate\Validation\ValidationException;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

abstract class AbstractStorageProvider implements StorageProviderContract
{
    public function __construct(protected StorageProvider $storageProvider) {}

    public static function editFields(): array
    {
        return [];
    }

    public function forgetCachedState(): void {}

    public function download(BackupFile $backupFile): array
    {
        abort(404, 'Downloading from this storage provider is not supported.');
    }

    /**
     * @return array{size: int, stream: Closure(): void}
     */
    protected function sftpDownload(SSH $ssh, string $path): array
    {
        $size = $ssh->fileSize($path);
        abort_if($size === null, 404, 'The backup file does not exist.');

        return [
            'size' => $size,
            'stream' => static function () use ($ssh, $path, $size): void {
                try {
                    $ssh->streamDownload($path, static function (string $chunk): void {
                        echo $chunk;
                        flush();
                    }, $size);
                } finally {
                    $ssh->disconnect();
                }
            },
        ];
    }

    /**
     * @return array{size: int, stream: Closure(): void}
     */
    protected function httpDownload(StreamInterface $body, int $size): array
    {
        return [
            'size' => $size,
            'stream' => static function () use ($body, $size): void {
                $bytes = 0;
                try {
                    while (! $body->eof()) {
                        $chunk = $body->read(65536);
                        $bytes += strlen($chunk);
                        echo $chunk;
                        flush();
                    }
                    if ($bytes !== $size) {
                        throw new RuntimeException('The backup download was interrupted.');
                    }
                } finally {
                    $body->close();
                }
            },
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function editableFields(): array
    {
        return [];
    }

    /**
     * @return array<int, string>
     */
    protected function secretFields(): array
    {
        return [];
    }

    public function editableData(): array
    {
        $credentials = $this->storageProvider->credentials;
        $data = [];

        foreach ($this->editableFields() as $field) {
            $data[$field] = $credentials[$field] ?? '';
        }

        return $data;
    }

    /**
     * @return array<int, string>
     */
    protected function secretDestinationFields(): array
    {
        return [];
    }

    public function editValidationRules(array $input): array
    {
        $createRules = $this->validationRules();
        $rules = [];

        $checkboxFields = static::checkboxFields();

        foreach ($this->editableFields() as $field) {
            $rules[$field] = ['sometimes', ...(array) ($createRules[$field] ?? 'nullable')];

            if (in_array($field, $checkboxFields, true)) {
                $rules[$field][] = 'boolean';
            }
        }

        foreach ($this->secretFields() as $field) {
            $optionalRules = array_filter(
                (array) ($createRules[$field] ?? []),
                fn (mixed $rule): bool => $rule !== 'required',
            );

            $rules[$field] = ['nullable', ...$optionalRules];
        }

        return $rules;
    }

    public function mergeEditData(array $input): array
    {
        $credentials = $this->storageProvider->credentials;
        $needsReconnect = false;
        $destinationChanged = false;
        $checkboxFields = static::checkboxFields();

        foreach ($this->editableFields() as $field) {
            if (! array_key_exists($field, $input)) {
                continue;
            }

            $value = in_array($field, $checkboxFields, true) ? (bool) $input[$field] : $input[$field];

            if ($this->isUnchanged($credentials[$field] ?? null, $value)) {
                continue;
            }

            $credentials[$field] = $value;
            $needsReconnect = true;
            $destinationChanged = $destinationChanged || in_array($field, $this->secretDestinationFields(), true);
        }

        foreach ($this->secretFields() as $field) {
            if (! isset($input[$field]) || $input[$field] === '') {
                if ($destinationChanged) {
                    throw ValidationException::withMessages([
                        $field => __('Enter the :field again when changing :fields.', [
                            'field' => $field,
                            'fields' => implode(', ', $this->secretDestinationFields()),
                        ]),
                    ]);
                }

                continue;
            }

            $credentials[$field] = $input[$field];
            $needsReconnect = true;
        }

        return [$credentials, $needsReconnect];
    }

    /**
     * @return array<int, string>
     */
    protected static function checkboxFields(): array
    {
        $fields = [];

        foreach (static::editFields() as $field) {
            $config = $field->toArray();

            if (($config['type'] ?? null) === 'checkbox') {
                $fields[] = $config['name'];
            }
        }

        return $fields;
    }

    private function isUnchanged(mixed $current, mixed $new): bool
    {
        if (is_bool($current) || is_bool($new)) {
            return (bool) $current === (bool) $new;
        }

        if (is_array($current) || is_array($new)) {
            return $current === $new;
        }

        return (string) ($current ?? '') === (string) ($new ?? '');
    }
}
