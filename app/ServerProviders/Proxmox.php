<?php

namespace App\ServerProviders;

use App\Exceptions\CouldNotConnectToProvider;
use App\Exceptions\ServerProviderError;
use App\Facades\Notifier;
use App\Notifications\FailedToDeleteServerFromProvider;
use App\Support\Cidr;
use App\ValidationRules\RestrictedIPAddressesRule;
use Closure;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class Proxmox extends AbstractProvider implements HasEditableCredentials
{
    /**
     * Memory and disk are in GiB.
     *
     * @var array<string, array{cores: int, memory: int, disk: int}>
     */
    private const PLANS = [
        'xsmall' => ['cores' => 1, 'memory' => 1, 'disk' => 25],
        'small' => ['cores' => 1, 'memory' => 2, 'disk' => 50],
        'medium' => ['cores' => 2, 'memory' => 4, 'disk' => 80],
        'large' => ['cores' => 4, 'memory' => 8, 'disk' => 160],
        'xlarge' => ['cores' => 8, 'memory' => 16, 'disk' => 320],
        '2xlarge' => ['cores' => 16, 'memory' => 32, 'disk' => 640],
    ];

    private const DISK_PATTERN = '/^(scsi|virtio|sata|ide)\d+$/';

    private const NODE_PATTERN = '/^[a-zA-Z0-9]([a-zA-Z0-9.-]*[a-zA-Z0-9])?$/';

    private const TASK_TIMEOUT_SECONDS = 30;

    public static function id(): string
    {
        return 'proxmox';
    }

    public static function templateField(string $os): string
    {
        return 'template_'.$os;
    }

    /**
     * @return array<int, string>
     */
    public static function templateFields(): array
    {
        return array_map(self::templateField(...), config('core.operating_systems'));
    }

    public function createRules(array $input): array
    {
        return [
            'region' => ['required', 'string', 'regex:'.self::NODE_PATTERN],
            'plan' => ['required', Rule::in(array_keys(self::PLANS))],
            'static_ip' => [
                'nullable',
                'string',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_string($value) || ! Cidr::isValid($value) || Cidr::isV6($value) || Cidr::prefix($value) === 0) {
                        $fail('The static IP must be an IPv4 address in CIDR notation, e.g. 192.168.1.50/24.');

                        return;
                    }

                    (new RestrictedIPAddressesRule)->validate($attribute, Cidr::address($value), $fail);
                },
            ],
            'gateway' => ['nullable', 'required_with:static_ip', 'ipv4'],
        ];
    }

    public function credentialValidationRules(array $input): array
    {
        $rules = [
            'api_url' => ['required', 'url:https'],
            'token_id' => ['required', 'string', 'regex:/^[^@\s]+@[^!\s]+![^\s=]+$/'],
            'token_secret' => ['required', 'string'],
            'verify_ssl' => ['nullable', 'boolean'],
            'storage' => ['nullable', 'string', 'regex:/^[a-zA-Z][a-zA-Z0-9._-]*$/'],
        ];

        foreach (self::templateFields() as $field) {
            $rules[$field] = ['nullable', 'integer', 'min:100'];
        }

        return $rules;
    }

    public function credentialData(array $input): array
    {
        $data = [
            'api_url' => self::baseUrl((string) $input['api_url']),
            'token_id' => $input['token_id'],
            'token_secret' => $input['token_secret'],
            'verify_ssl' => filter_var($input['verify_ssl'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'storage' => $input['storage'] ?? null,
        ];

        foreach (self::templateFields() as $field) {
            $data[$field] = self::templateVmid($input[$field] ?? null);
        }

        return $data;
    }

    public function editableData(): array
    {
        return collect(self::templateFields())
            ->mapWithKeys(fn (string $field): array => [$field => $this->serverProvider->credentials[$field] ?? null])
            ->all();
    }

    public function editRules(array $input): array
    {
        return Arr::only($this->credentialValidationRules($input), self::templateFields());
    }

    public function editCredentials(array $input): array
    {
        $credentials = $this->serverProvider->credentials;

        foreach (self::templateFields() as $field) {
            if (! array_key_exists($field, $input)) {
                continue;
            }

            $vmid = self::templateVmid($input[$field]);

            if ($vmid !== ($credentials[$field] ?? null)) {
                $credentials[$field] = $vmid;
            }
        }

        return $credentials;
    }

    public function data(array $input): array
    {
        return [
            'region' => $input['region'],
            'plan' => $input['plan'],
            'static_ip' => $input['static_ip'] ?? null,
            'gateway' => $input['gateway'] ?? null,
        ];
    }

    /**
     * Besides the token, verifies every mapped template exists, is a template and
     * carries a cloud-init drive, so a broken mapping fails here rather than mid-install.
     *
     * @throws CouldNotConnectToProvider
     * @throws ValidationException
     * @throws ServerProviderError
     * @throws ConnectionException
     */
    public function connect(array $credentials): bool
    {
        $templates = collect(self::templateFields())
            ->mapWithKeys(fn (string $field): array => [$field => (int) ($credentials[$field] ?? 0)])
            ->filter();

        if ($templates->isEmpty()) {
            throw ValidationException::withMessages([
                last(self::templateFields()) => 'Map at least one Ubuntu version to a template.',
            ]);
        }

        try {
            $vms = collect($this->request('GET', '/cluster/resources', ['type' => 'vm'], $credentials));
        } catch (ServerProviderError|ConnectionException) {
            throw new CouldNotConnectToProvider('Proxmox');
        }

        if ($vms->isEmpty()) {
            throw ValidationException::withMessages([
                'token_id' => 'The API token cannot see any VMs. If Privilege Separation is enabled on the token, grant the roles to the token itself or recreate it without privilege separation.',
            ]);
        }

        $errors = [];

        foreach ($templates as $field => $vmid) {
            /** @var array{node?: string, template?: int, type?: string}|null $vm */
            $vm = $vms->firstWhere('vmid', $vmid);

            if (! $vm || ! isset($vm['node'])) {
                $errors[$field] = "VM {$vmid} was not found or the API token cannot access it.";

                continue;
            }

            if (empty($vm['template']) || ($vm['type'] ?? null) !== 'qemu') {
                $errors[$field] = "VM {$vmid} is not a QEMU template.";

                continue;
            }

            $config = $this->request('GET', '/nodes/'.rawurlencode($vm['node']).'/qemu/'.$vmid.'/config', [], $credentials);

            if (! $this->hasCloudInitDrive($config)) {
                $errors[$field] = "Template {$vmid} has no cloud-init drive.";
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return true;
    }

    /**
     * @return array<string, array{label: string, available: bool}>
     */
    public function plans(?string $region): array
    {
        try {
            /** @var array{maxcpu?: int, maxmem?: int}|null $node */
            $node = collect($this->request('GET', '/nodes'))->firstWhere('node', $region);
        } catch (Exception) {
            $node = null;
        }

        return collect(self::PLANS)
            ->map(fn (array $plan, string $name): array => [
                'label' => __('server_providers.plan', [
                    'name' => $name,
                    'cpu' => $plan['cores'],
                    'memory' => $plan['memory'],
                    'disk' => $plan['disk'],
                ]),
                'available' => ! isset($node['maxcpu'], $node['maxmem'])
                    || ($plan['cores'] <= $node['maxcpu'] && $node['maxmem'] >= $plan['memory'] * 1024 ** 3),
            ])
            ->all();
    }

    public function regions(): array
    {
        try {
            return collect($this->request('GET', '/nodes'))
                ->where('status', 'online')
                ->mapWithKeys(fn (array $node): array => [$node['node'] => $node['node']])
                ->sort()
                ->all();
        } catch (Exception) {
            return [];
        }
    }

    /**
     * Starts a full clone of the mapped template and returns without waiting for it;
     * `isRunning()` finishes provisioning once the clone task is done.
     *
     * @throws ServerProviderError
     * @throws ConnectionException
     */
    public function create(): void
    {
        $credentials = $this->serverProvider->getCredentials();
        $templateId = (int) ($credentials[self::templateField($this->server->os->value)] ?? 0);

        if ($templateId === 0) {
            throw new ServerProviderError('No Proxmox template is mapped to Ubuntu '.$this->server->os->getVersion().' on this connection.');
        }

        $this->generateKeyPair();

        /** @var array{node?: string, template?: int}|null $template */
        $template = collect($this->request('GET', '/cluster/resources', ['type' => 'vm']))->firstWhere('vmid', $templateId);

        if (! $template || empty($template['template']) || ! isset($template['node'])) {
            throw new ServerProviderError("Proxmox template {$templateId} was not found.");
        }

        $node = $this->server->provider_data['region'];
        $vmid = (int) $this->request('GET', '/cluster/nextid');

        $clone = [
            'newid' => $vmid,
            'name' => Str::slug($this->server->name) ?: 'server-'.$this->server->id,
            'description' => $this->marker(),
            'full' => 1,
        ];

        if ($template['node'] !== $node) {
            $clone['target'] = $node;
        }

        if (! empty($credentials['storage'])) {
            $clone['storage'] = $credentials['storage'];
        }

        $task = $this->request('POST', '/nodes/'.rawurlencode($template['node']).'/qemu/'.$templateId.'/clone', $clone);

        $this->server->jsonUpdate('provider_data', 'vmid', $vmid, false);
        $this->server->jsonUpdate('provider_data', 'task', $task, false);

        if (! empty($this->server->provider_data['static_ip'])) {
            $this->server->ip = Cidr::address($this->server->provider_data['static_ip']);
        }

        $this->server->save();
    }

    /**
     * Polled by the install job. While the clone task runs this returns false; once it
     * finishes, the VM is configured and started. With DHCP, the IP comes from the guest agent.
     *
     * @throws ServerProviderError
     */
    public function isRunning(): bool
    {
        try {
            if (isset($this->server->provider_data['task'])) {
                if ($this->taskFinished($this->server->provider_data['task'])) {
                    $this->provision();
                }

                return false;
            }

            if (! $this->vmRunning()) {
                return false;
            }

            if (! $this->server->ip) {
                $ip = $this->guestIp();

                if ($ip === null) {
                    return false;
                }

                $this->server->ip = $ip;
                $this->server->save();
            }
        } catch (ConnectionException) {
            return false;
        }

        return true;
    }

    public function delete(): void
    {
        if (! isset($this->server->provider_data['vmid'])) {
            return;
        }

        try {
            $this->destroyVm();
        } catch (Exception) {
            Notifier::send($this->server, new FailedToDeleteServerFromProvider($this->server));
        }
    }

    /**
     * Refuses to touch a VM without this server's marker: the VMID of a failed clone is
     * freed and Proxmox can hand it out again to an unrelated VM.
     *
     * @throws ServerProviderError
     * @throws ConnectionException
     */
    private function destroyVm(): void
    {
        $config = $this->request('GET', $this->vmPath().'/config');

        if (! str_contains((string) ($config['description'] ?? ''), $this->marker())) {
            throw new ServerProviderError('Proxmox VM '.$this->server->provider_data['vmid'].' was not created for this server.');
        }

        if ($this->vmRunning()) {
            $this->waitForTask((string) $this->request('POST', $this->vmPath().'/status/stop'));
        }

        $this->request('DELETE', $this->vmPath(), [
            'purge' => 1,
            'destroy-unreferenced-disks' => 1,
        ]);
    }

    private function marker(): string
    {
        return 'Managed by Vito (server #'.$this->server->id.')';
    }

    /**
     * Safe to repeat when an install is retried: the config and resize are idempotent
     * and a VM that is already running is not started again.
     *
     * @throws ServerProviderError
     * @throws ConnectionException
     */
    private function provision(): void
    {
        $plan = self::PLANS[$this->server->provider_data['plan']];
        $staticIp = $this->server->provider_data['static_ip'] ?? null;

        $config = [
            'cores' => $plan['cores'],
            'memory' => $plan['memory'] * 1024,
            'ciuser' => 'root',
            'sshkeys' => rawurlencode(trim($this->server->sshKey()['public_key'])),
            'ipconfig0' => $staticIp ? 'ip='.$staticIp.',gw='.$this->server->provider_data['gateway'] : 'ip=dhcp',
            'ciupgrade' => 0,
        ];

        if (! $staticIp) {
            $config['agent'] = 1;
        }

        $this->request('PUT', $this->vmPath().'/config', $config);
        $this->resizeBootDisk($plan['disk']);

        if (! $this->vmRunning()) {
            $this->waitForTask((string) $this->request('POST', $this->vmPath().'/status/start'));
        }

        $this->server->jsonForget('provider_data', 'task');
    }

    /**
     * @throws ServerProviderError
     * @throws ConnectionException
     */
    private function vmRunning(): bool
    {
        return ($this->request('GET', $this->vmPath().'/status/current')['status'] ?? null) === 'running';
    }

    /**
     * @throws ServerProviderError
     * @throws ConnectionException
     */
    private function resizeBootDisk(int $size): void
    {
        $config = $this->request('GET', $this->vmPath().'/config');
        $disk = $this->bootDisk($config);

        if ($disk === null || $this->diskSize((string) $config[$disk]) >= $size) {
            return;
        }

        $task = $this->request('PUT', $this->vmPath().'/resize', [
            'disk' => $disk,
            'size' => $size.'G',
        ]);

        if (is_string($task) && str_starts_with($task, 'UPID:')) {
            $this->waitForTask($task);
        }
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function bootDisk(array $config): ?string
    {
        $disks = collect($config)->filter(fn (mixed $value, int|string $key): bool => preg_match(self::DISK_PATTERN, (string) $key) === 1
            && ! str_contains((string) $value, 'media=cdrom')
            && ! str_contains((string) $value, 'cloudinit'));

        $order = explode(';', Str::after((string) ($config['boot'] ?? ''), 'order='));

        return collect($order)->first(fn (string $key): bool => $disks->has($key)) ?? $disks->keys()->first();
    }

    /**
     * Size of a drive definition such as `local-lvm:vm-100-disk-0,size=3584M`, in GiB.
     */
    private function diskSize(string $disk): float
    {
        if (preg_match('/(?:^|,)size=(\d+(?:\.\d+)?)([KMGT]?)/', $disk, $matches) !== 1) {
            return 0;
        }

        return (float) $matches[1] * match ($matches[2]) {
            'K' => 1 / 1024 ** 2,
            'M' => 1 / 1024,
            'G' => 1,
            'T' => 1024,
            default => 1 / 1024 ** 3,
        };
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function hasCloudInitDrive(array $config): bool
    {
        return collect($config)->contains(fn (mixed $value, int|string $key): bool => preg_match(self::DISK_PATTERN, (string) $key) === 1
            && str_contains((string) $value, 'cloudinit'));
    }

    /**
     * @throws ConnectionException
     */
    private function guestIp(): ?string
    {
        try {
            $interfaces = $this->request('GET', $this->vmPath().'/agent/network-get-interfaces');
        } catch (ServerProviderError) {
            return null;
        }

        foreach ($interfaces['result'] ?? [] as $interface) {
            foreach ($interface['ip-addresses'] ?? [] as $address) {
                $ip = $address['ip-address'] ?? null;

                if (($address['ip-address-type'] ?? null) === 'ipv4'
                    && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_RES_RANGE) !== false
                    && ! in_array($ip, config('core.restricted_ip_addresses'), true)) {
                    return $ip;
                }
            }
        }

        return null;
    }

    /**
     * @throws ServerProviderError
     * @throws ConnectionException
     */
    private function taskFinished(string $upid): bool
    {
        $node = explode(':', $upid)[1] ?? '';
        $task = $this->request('GET', '/nodes/'.rawurlencode($node).'/tasks/'.rawurlencode($upid).'/status');

        if (($task['status'] ?? null) !== 'stopped') {
            return false;
        }

        $exitStatus = (string) ($task['exitstatus'] ?? '');

        if ($exitStatus !== 'OK' && ! str_starts_with($exitStatus, 'WARNINGS')) {
            throw new ServerProviderError('Proxmox task failed: '.$exitStatus);
        }

        return true;
    }

    /**
     * @throws ServerProviderError
     * @throws ConnectionException
     */
    private function waitForTask(string $upid): void
    {
        for ($second = 0; $second < self::TASK_TIMEOUT_SECONDS; $second++) {
            if ($this->taskFinished($upid)) {
                return;
            }

            Sleep::sleep(1);
        }

        throw new ServerProviderError('Timed out waiting for the Proxmox task to finish.');
    }

    private function vmPath(): string
    {
        return '/nodes/'.rawurlencode((string) $this->server->provider_data['region']).'/qemu/'.(int) $this->server->provider_data['vmid'];
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>|null  $credentials
     *
     * @throws ServerProviderError
     * @throws ConnectionException
     */
    private function request(string $method, string $path, array $data = [], ?array $credentials = null): mixed
    {
        $credentials ??= $this->serverProvider->getCredentials();

        $response = Http::withHeaders([
            'Authorization' => 'PVEAPIToken='.$credentials['token_id'].'='.$credentials['token_secret'],
        ])
            ->withOptions(['verify' => filter_var($credentials['verify_ssl'] ?? true, FILTER_VALIDATE_BOOLEAN)])
            ->acceptJson()
            ->asForm()
            ->timeout(30)
            ->send($method, self::baseUrl((string) $credentials['api_url']).'/api2/json'.$path, in_array($method, ['POST', 'PUT'], true)
                ? ['form_params' => $data]
                : ['query' => $data]);

        if (! $response->successful()) {
            throw new ServerProviderError($this->errorMessage($response));
        }

        if (! is_array($response->json()) || ! array_key_exists('data', $response->json())) {
            throw new ServerProviderError('Unexpected response from '.self::baseUrl((string) $credentials['api_url']).'. Is it a Proxmox VE API URL?');
        }

        return $response->json('data');
    }

    /**
     * The API always lives at `/api2/json` on the host root, so any path, query or
     * fragment (e.g. a URL copied from the web UI) is dropped.
     */
    private static function baseUrl(string $url): string
    {
        $parts = parse_url($url);

        return 'https://'.($parts['host'] ?? '').(isset($parts['port']) ? ':'.$parts['port'] : '');
    }

    private static function templateVmid(mixed $value): ?int
    {
        return empty($value) ? null : (int) $value;
    }

    private function errorMessage(Response $response): string
    {
        $errors = collect((array) $response->json('errors'))
            ->map(fn (mixed $message, int|string $field): string => $field.': '.trim((string) $message))
            ->implode(' ');

        return trim('Proxmox API error ('.$response->status().'): '.trim((string) ($response->json('message') ?: $response->reason())).' '.$errors);
    }
}
