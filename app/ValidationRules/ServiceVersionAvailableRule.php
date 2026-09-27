<?php

namespace App\ValidationRules;

use App\Enums\OperatingSystem;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

/**
 * Rejects a service version its registration marks unavailable on the given
 * operating system. The service is read from the sibling `name` field, so the
 * rule works on both `version` and `services.*.version`.
 */
class ServiceVersionAvailableRule implements DataAwareRule, ValidationRule
{
    /**
     * @var array<string, mixed>
     */
    private array $data = [];

    public function __construct(private ?OperatingSystem $os) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $service = data_get($this->data, Str::replaceLast('version', 'name', $attribute));

        if (! $this->os || ! is_scalar($value) || ! is_string($service) || ! array_key_exists($service, config('service.services'))) {
            return;
        }

        $version = (string) $value;

        if (in_array($version, (array) config("service.services.$service.unavailable_versions.{$this->os->value}"), true)) {
            $fail(':service :version is not available on Ubuntu :os.')->translate([
                'service' => config("service.services.$service.label"),
                'version' => $version,
                'os' => $this->os->getVersion(),
            ]);
        }
    }
}
