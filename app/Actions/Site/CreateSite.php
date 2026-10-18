<?php

namespace App\Actions\Site;

use App\Actions\HostedDomain\AssertHostAvailable;
use App\Enums\HostedDomainStatus;
use App\Enums\HostedDomainType;
use App\Enums\SiteStatus;
use App\Exceptions\RepositoryNotFound;
use App\Exceptions\RepositoryPermissionDenied;
use App\Exceptions\SourceControlIsNotConnected;
use App\Jobs\Site\CreateJob;
use App\Models\IsolatedUser;
use App\Models\Server;
use App\Models\Service;
use App\Models\Site;
use App\Models\User;
use App\Services\Webserver\Webserver;
use App\Tooling\ToolingRegistry;
use App\ValidationRules\DomainRule;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class CreateSite
{
    private const ISOLATED_USER_PATTERN = '/^[a-z_][a-z0-9_-]*[a-z0-9]$/';

    /**
     * @param  array<string, mixed>  $input
     *
     * @throws Throwable
     */
    public function create(Server $server, array $input, User $actor): Site
    {
        $input = $this->lockRuntimeVersionsToExistingUser($server, $input);

        $this->validate($server, $input);

        $lock = $server->isolatedUserLock($input['user']);
        try {
            $lock->block(30);
        } catch (LockTimeoutException) {
            throw ValidationException::withMessages(['user' => 'Another operation on this Linux user is in progress. Please retry.']);
        }

        try {
            DB::beginTransaction();
            $input = $this->lockRuntimeVersionsToExistingUser($server, $input);
            $user = $input['user'];

            $isolatedUser = $user !== $server->getSshUser()
                ? IsolatedUser::query()->firstOrCreate(
                    ['server_id' => $server->id, 'username' => $user],
                )
                : null;

            $site = new Site([
                'server_id' => $server->id,
                'isolated_user_id' => $isolatedUser?->id,
                'type' => $input['type'],
                'domain' => $input['domain'],
                'user' => $user,
                'path' => '/home/'.$user.'/'.$input['domain'],
                'status' => SiteStatus::INSTALLING,
            ]);

            foreach ($site->type()->requiredServices() as $requiredService) {
                if (! $server->service($requiredService)) {
                    throw ValidationException::withMessages([
                        'type' => "The site type requires a {$requiredService} service to be installed.",
                    ]);
                }
            }

            // fields based on the type
            $site->fill($site->type()->createFields($input));

            /** @var Service $webserver */
            $webserver = $server->webserver();
            /** @var Webserver $webserverHandler */
            $webserverHandler = $webserver->handler();
            $site->fill($webserverHandler->siteDefaults());

            // check has access to repository
            try {
                if ($site->sourceControl) {
                    Gate::forUser($actor)->authorize('view', $site->sourceControl);

                    $site->sourceControl->getRepo($site->repository);
                }
            } catch (SourceControlIsNotConnected) {
                throw ValidationException::withMessages([
                    'source_control' => 'Source control is not connected',
                ]);
            } catch (RepositoryPermissionDenied) {
                throw ValidationException::withMessages([
                    'repository' => 'You do not have permission to access this repository',
                ]);
            } catch (RepositoryNotFound) {
                throw ValidationException::withMessages([
                    'repository' => 'Repository not found',
                ]);
            }

            // set type data
            $site->type_data = $site->type()->data($input);

            // save
            $site->save();

            $defaultSslMethod = $webserverHandler->defaultSslMethod();

            $site->hostedDomains()->create([
                'domain' => $site->domain,
                'type' => HostedDomainType::PRIMARY,
                'status' => HostedDomainStatus::CREATING,
                'ssl_method' => $defaultSslMethod,
            ]);

            foreach ($input['aliases'] ?? [] as $alias) {
                $site->hostedDomains()->create([
                    'domain' => $alias,
                    'type' => HostedDomainType::ALIAS,
                    'status' => HostedDomainStatus::CREATING,
                    'ssl_method' => $defaultSslMethod,
                ]);
            }

            // create base commands if any
            $site->commands()->createMany($site->type()->baseCommands());

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            if ($e instanceof AuthorizationException || $e instanceof RequestException) {
                throw $e;
            }

            throw ValidationException::withMessages([
                'type' => $e->getMessage(),
            ]);
        } finally {
            $lock->release();
        }

        dispatch(new CreateJob($site));

        return $site;
    }

    private function validate(Server $server, array $input): void
    {
        $rules = [
            'type' => [
                'required',
                Rule::in(array_keys(config('site.types'))),
            ],
            'domain' => [
                'required',
                new DomainRule,
            ],
            'aliases' => [
                'nullable',
                'array',
            ],
            'aliases.*' => [
                'bail',
                'required',
                'string',
                new DomainRule,
            ],
            'user' => [
                'required',
                'regex:'.self::ISOLATED_USER_PATTERN,
                'min:3',
                'max:32',
                Rule::notIn(array_unique(array_merge(
                    config('core.reserved_user_names'),
                    [$server->getSshUser()]
                ))),
            ],
        ];

        Validator::make($input, array_merge($rules, $this->typeRules($server, $input)))->validate();

        app(AssertHostAvailable::class)->assert($server, $input['domain'], 'domain');
        foreach ($input['aliases'] ?? [] as $index => $alias) {
            app(AssertHostAvailable::class)->assert($server, $alias, 'aliases.'.$index);
        }
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, array<string>>
     */
    private function typeRules(Server $server, array $input): array
    {
        if (! isset($input['type']) || ! config('site.types.'.$input['type'])) {
            return [];
        }

        $site = new Site([
            'server_id' => $server->id,
            'type' => $input['type']]
        );

        return $site->type()->createRules($input);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function lockRuntimeVersionsToExistingUser(Server $server, array $input): array
    {
        $user = isset($input['user']) && is_string($input['user']) ? $input['user'] : '';

        if ($user === '' || preg_match(self::ISOLATED_USER_PATTERN, $user) !== 1) {
            return $input;
        }

        $iuser = IsolatedUser::query()
            ->where('server_id', $server->id)
            ->where('username', $user)
            ->first();

        if (! $iuser instanceof IsolatedUser) {
            return $input;
        }

        foreach (ToolingRegistry::all() as $id => $tool) {
            $allowed = $tool::supportedVersionsWithNone();
            $existing = $iuser->toolingVersion($id);

            if ($existing === null || ! in_array($existing, $allowed, true) || $existing === 'none') {
                continue;
            }

            $field = $tool::typeDataKey();
            $submitted = $input[$field] ?? null;

            if (is_string($submitted) && $submitted !== '' && $submitted !== $existing) {
                throw ValidationException::withMessages([
                    $field => "Isolated user '{$user}' already has {$tool::label()} {$existing} installed; this cannot be changed.",
                ]);
            }

            $input[$field] = $existing;
        }

        return $input;
    }
}
