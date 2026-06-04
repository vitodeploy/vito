<?php

namespace App\Services\Fail2ban;

use App\DTOs\ServiceLog;
use App\Exceptions\SSHError;
use App\Services\AbstractService;
use App\Services\HasLogs;
use Closure;
use Illuminate\Validation\Rule;

class Fail2ban extends AbstractService implements HasLogs
{
    public static function id(): string
    {
        return 'fail2ban';
    }

    public static function type(): string
    {
        return 'fail2ban';
    }

    public function unit(): string
    {
        return 'fail2ban';
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function jailValidationRules(): array
    {
        return [
            'maxretry' => ['nullable', 'integer', 'min:1', 'max:100'],
            'findtime' => ['nullable', 'string', 'max:10', 'regex:/^\d+[smhdw]?$/'],
            'bantime' => ['nullable', 'string', 'max:10', 'regex:/^(-1|\d+[smhdw]?)$/'],
            'ignoreip' => ['nullable', 'string', 'max:1000', 'regex:/^[0-9a-fA-F.:\/ ]*$/'],
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function creationRules(array $input): array
    {
        return array_merge([
            'type' => [
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($this->service->server->fail2ban()) {
                        $fail('Fail2ban is already installed on this server.');
                    }
                },
            ],
            'version' => [
                'required',
                Rule::in(['latest']),
            ],
        ], self::jailValidationRules());
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function creationData(array $input): array
    {
        return [
            'maxretry' => (int) ($input['maxretry'] ?? 5),
            'findtime' => $input['findtime'] ?? '10m',
            'bantime' => $input['bantime'] ?? '10m',
            'ignoreip' => $input['ignoreip'] ?? '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function data(): array
    {
        return [
            'maxretry' => $this->service->type_data['maxretry'] ?? 5,
            'findtime' => $this->service->type_data['findtime'] ?? '10m',
            'bantime' => $this->service->type_data['bantime'] ?? '10m',
            'ignoreip' => $this->service->type_data['ignoreip'] ?? '',
        ];
    }

    /**
     * @throws SSHError
     */
    public function install(): void
    {
        $this->service->server->ssh()
            ->setLog($this->service->log)
            ->exec(
                view('ssh.services.fail2ban.install', $this->jailData()),
                'install-fail2ban'
            );
        event('service.installed', $this->service);
        $this->service->server->os()->cleanup();
    }

    /**
     * @throws SSHError
     */
    public function uninstall(): void
    {
        $this->service->server->ssh()->exec(
            view('ssh.services.fail2ban.uninstall'),
            'uninstall-fail2ban'
        );
        event('service.uninstalled', $this->service);
    }

    /**
     * @throws SSHError
     */
    public function configure(): void
    {
        $this->service->server->ssh()->exec(
            view('ssh.services.fail2ban.configure', $this->jailData()),
            'configure-fail2ban'
        );
    }

    public function version(): string
    {
        $version = $this->service->server->ssh()->exec(
            'fail2ban-client version 2>/dev/null | tr -d "\n"'
        );

        return trim($version);
    }

    /**
     * @return array<int, ServiceLog>
     */
    public function logs(): array
    {
        return [
            new ServiceLog(
                key: 'fail2ban:journal',
                serviceLabel: 'Fail2ban',
                label: 'General log',
                source: ServiceLog::SOURCE_JOURNAL,
                target: 'fail2ban.service',
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function jailData(): array
    {
        return [
            'port' => $this->service->server->port ?? 22,
            'maxretry' => (int) ($this->service->type_data['maxretry'] ?? 5),
            'findtime' => $this->service->type_data['findtime'] ?? '10m',
            'bantime' => $this->service->type_data['bantime'] ?? '10m',
            'ignoreip' => trim((string) ($this->service->type_data['ignoreip'] ?? '')),
        ];
    }
}
