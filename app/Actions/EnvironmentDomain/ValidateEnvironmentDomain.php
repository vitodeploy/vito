<?php

namespace App\Actions\EnvironmentDomain;

use App\Actions\HostedDomain\AssertHostAvailable;
use App\Enums\ApplicationStatus;
use App\Enums\ComponentStatus;
use App\Enums\ComponentType;
use App\Enums\EnvironmentStatus;
use App\Enums\HostedDomainType;
use App\Enums\SslMethod;
use App\Models\Application;
use App\Models\Domain;
use App\Models\Environment;
use App\Models\EnvironmentDomain;
use App\Models\Server;
use App\Models\Ssl;
use Closure;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ValidateEnvironmentDomain
{
    /**
     * @template T
     * @param Closure(): T $callback
     * @return T
     */
    public function run(Environment $environment, Closure $callback): mixed
    {
        $serverIds = $environment->components()->distinct()->orderBy('server_id')->pluck('server_id')->all();
        $operation = fn () => DB::transaction(function () use ($environment, $callback, $serverIds) {
            Application::query()->lockForUpdate()->findOrFail($environment->application_id);
            Environment::query()->lockForUpdate()->findOrFail($environment->id);
            Server::query()->whereKey($serverIds)->orderBy('id')->lockForUpdate()->get();
            $environment->refresh();
            if ($environment->components()->distinct()->orderBy('server_id')->pluck('server_id')->all() !== $serverIds) {
                throw ValidationException::withMessages(['host' => 'Components changed. Please retry.']);
            }
            $this->ready($environment);

            return $callback();
        });
        foreach (array_reverse($serverIds) as $serverId) {
            $next = $operation;
            $operation = fn () => app(AssertHostAvailable::class)->run($serverId, 'host', $next);
        }
        foreach (array_reverse($serverIds) as $serverId) {
            $next = $operation;
            $operation = fn () => Cache::lock('unique-queue:server-'.$serverId, 600)->block(10, $next);
        }
        try {
            return $operation();
        } catch (LockTimeoutException) {
            throw ValidationException::withMessages(['host' => 'Another operation on a target server is in progress. Please retry.']);
        }
    }

    public function ready(Environment $environment): void
    {
        if ($environment->status !== EnvironmentStatus::READY || $environment->application->status !== ApplicationStatus::READY) {
            throw ValidationException::withMessages(['host' => 'Domains cannot change during teardown.']);
        }
    }

    /** @param array<string, mixed> $input */
    public function validate(Environment $environment, array $input, ?EnvironmentDomain $domain = null): EnvironmentDomain
    {
        if (is_string($input['host'] ?? null)) {
            $input['host'] = strtolower(trim($input['host']));
        }
        $redirect = ($input['type'] ?? null) === HostedDomainType::REDIRECT->value;
        $data = Validator::make($input, [
            'host' => ['required', 'string', 'max:253', 'regex:/\A(?:\*\.)?([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}\z/', Rule::unique('environment_domains', 'host')->where('environment_id', $environment->id)->ignore($domain?->id)],
            'type' => ['required', Rule::in([HostedDomainType::ALIAS->value, HostedDomainType::REDIRECT->value])],
            'component_id' => [Rule::requiredIf(! $redirect), 'nullable', 'integer'],
            'redirect_to_domain_id' => [Rule::requiredIf($redirect), 'nullable', 'integer'],
            'ssl_method' => ['required', Rule::enum(SslMethod::class)],
            'ssl_id' => [Rule::requiredIf(($input['ssl_method'] ?? null) === SslMethod::CUSTOM->value), 'nullable', 'integer'],
            'force_ssl' => ['required', 'boolean'],
        ])->validate();

        $target = $redirect ? $environment->domains()->whereKey($data['redirect_to_domain_id'])->where('type', HostedDomainType::ALIAS)->first() : null;
        if ($redirect && (! $target || $target->id === $domain?->id || str_starts_with($target->host, '*.'))) {
            throw ValidationException::withMessages(['redirect_to_domain_id' => 'Select a non-wildcard alias in this environment.']);
        }
        $component = $environment->components()->with('server', 'capabilities')->find($target->component_id ?? $data['component_id']);
        if (! $component || $component->status !== ComponentStatus::READY
            || ! $component->server->isReady() || $component->server->project_id !== $environment->application->project_id
            || ($component->type !== ComponentType::LOAD_BALANCER && ! $component->capabilities->contains(fn ($capability): bool => $capability->capability === 'web' && $capability->status === ComponentStatus::READY))) {
            throw ValidationException::withMessages(['component_id' => 'Select a ready web component or load balancer in this environment.']);
        }
        $webserver = $component->server->webserver();
        if (! $webserver || ! in_array($webserver->name, ['nginx', 'caddy'], true)) {
            throw ValidationException::withMessages(['component_id' => 'The target needs an Nginx or Caddy webserver.']);
        }
        if ($domain?->exists && $domain->redirects()->exists() && ($redirect || $component->id !== $domain->component_id || str_starts_with($data['host'], '*.'))) {
            throw ValidationException::withMessages(['host' => 'Remove the redirects using this alias before changing its target or type.']);
        }
        if (str_starts_with($data['host'], '*.')) {
            $this->wildcardDomain($environment, $data['host']);
        }
        if ($data['ssl_method'] === SslMethod::CUSTOM->value) {
            $ssl = Ssl::activeServerLevel($component->server_id)->whereKey($data['ssl_id'])->first();
            if (! $ssl || ! $ssl->expires_at?->isFuture() || ! is_array($ssl->domains) || $ssl->domains === [] || ! $ssl->coversDomain($data['host'])) {
                throw ValidationException::withMessages(['ssl_id' => 'Select an active, unexpired server certificate covering this host on the target server.']);
            }
        }
        app(AssertHostAvailable::class)->assert($component->server, $data['host'], 'host', $domain);

        $domain ??= new EnvironmentDomain;
        $domain->environment_id = $environment->id;
        $domain->component_id = $component->id;
        $domain->host = $data['host'];
        $domain->type = HostedDomainType::from($data['type']);
        $domain->redirect_to_domain_id = $target?->id;
        $domain->force_ssl = $data['force_ssl'];
        $method = SslMethod::from($data['ssl_method']);
        if ($method === SslMethod::CUSTOM) {
            $domain->ssl_id = $data['ssl_id'];
        } elseif ($method !== $domain->ssl_method || $domain->isDirty(['host', 'component_id'])) {
            $domain->ssl_id = null;
        }
        $domain->ssl_method = $method;
        $domain->unsetRelations();

        return $domain;
    }

    public function wildcardDomain(Environment $environment, string $host): Domain
    {
        $domain = Domain::query()->where('project_id', $environment->application->project_id)
            ->where('domain', substr($host, 2))->whereHas('dnsProvider', fn ($query) => $query->where('project_id', $environment->application->project_id))->first();
        if (! $domain) {
            throw ValidationException::withMessages(['host' => 'Wildcard hosts need the matching project domain with a DNS provider.']);
        }

        return $domain;
    }
}
