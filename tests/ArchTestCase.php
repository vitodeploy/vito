<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use InvalidArgumentException;

/**
 * Base case for the architecture suite, and the single registry of exceptions
 * to its rules.
 *
 * Read `.github/instructions/architecture-tests.instructions.md` before
 * touching EXCEPTIONS. Entries may only be added with the maintainer's explicit
 * permission, and only when a rule is added or changed — never to make a
 * failing rule pass.
 */
abstract class ArchTestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Every deviation from an architecture rule that the current implementation
     * relies on. Each entry exempts real code from a real standard, so the rule
     * stops protecting it. Lists may shrink freely; they may not grow.
     *
     * @var array<string, array{reason: string, entries: array<int, string>}>
     */
    public const array EXCEPTIONS = [
        'foundation.var-export' => [
            'reason' => 'Renders booleans as literal true/false for the Supervisor config file.',
            'entries' => [
                'App\Services\ProcessManager\Supervisor',
            ],
        ],
        'foundation.non-cryptographic-hashing' => [
            'reason' => 'Hashes for cache keys and content fingerprints only — never for authentication, signing or password storage.',
            'entries' => [
                'App\Actions\Bootstrap\GetBootstrap',
                'App\Actions\Ziggy\GetZiggyRoutes',
                'App\Helpers\Apr1Hasher',
                'App\SourceControlProviders',
            ],
        ],
        'foundation.local-shell' => [
            'reason' => 'Runs exec()/shell_exec() on the Vito host itself for key generation and binary discovery, not against a managed server.',
            'entries' => [
                'App\Support\helpers',
                'App\Console\Commands\GenerateKeysCommand',
            ],
        ],
        'foundation.assert' => [
            'reason' => 'Uses assert() to narrow a provider type for static analysis.',
            'entries' => [
                'App\Actions\StorageProvider\DeleteStorageProvider',
            ],
        ],
        'foundation.blocking-sleep' => [
            'reason' => 'Polls Let\'s Encrypt with a blocking sleep() loop, so Sleep::fake() cannot intercept it and the test suite really waits.',
            'entries' => [
                'App\Jobs\SSL\CreateLetsEncryptWildcardSslJob',
            ],
        ],
        'foundation.non-throwable-in-exceptions' => [
            'reason' => 'The framework exception handler lives in the exceptions namespace but is not itself throwable.',
            'entries' => [
                'App\Exceptions\Handler',
            ],
        ],
        'foundation.missing-return-type' => [
            'reason' => 'Agent overrides an untyped third-party base class; the other two are drift.',
            'entries' => [
                'App\Helpers\Agent',
                'App\Helpers\SSH',
                'App\Http\Controllers\Admin\PluginController',
            ],
        ],
        'actions.accepting-request' => [
            'reason' => 'Reads the OAuth callback Request directly instead of receiving an input array.',
            'entries' => [
                'App\Actions\StorageProvider\ConnectDropbox',
            ],
        ],
        'http.controllers-validating' => [
            'reason' => 'Validates in the controller instead of delegating to the Action.',
            'entries' => [
                'App\Http\Controllers\API\ServerController',
            ],
        ],
        'http.controllers-querying' => [
            'reason' => 'Builds cross-model search queries with the DB facade rather than Eloquent.',
            'entries' => [
                'App\Http\Controllers\SearchController',
            ],
        ],
        'http.controllers-dispatching-jobs' => [
            'reason' => 'Dispatches jobs straight from the controller, so the orchestration is invisible to the Action layer.',
            'entries' => [
                'App\Http\Controllers\HostedDomainController',
                'App\Http\Controllers\NetworkController',
                'App\Http\Controllers\SiteStatsController',
            ],
        ],
        'http.resources-calling-actions' => [
            'reason' => 'Runs a stranding check while serialising, so rendering the resource does work.',
            'entries' => [
                'App\Http\Resources\NetworkResource',
            ],
        ],
        'models.foreign-base-class' => [
            'reason' => 'Extends an authentication or Sanctum base class that AbstractModel cannot sit under.',
            'entries' => [
                'App\Models\User',
                'App\Models\PersonalAccessToken',
            ],
        ],
        'models.not-on-abstract-model' => [
            'reason' => 'Extends Model directly, so it misses HasTimezoneTimestamps and jsonUpdate().',
            'entries' => [
                'App\Models\Metric',
                'App\Models\Plugin',
                'App\Models\PluginError',
                'App\Models\Project',
                'App\Models\UserProject',
                'App\Models\Workflow',
                'App\Models\WorkflowRun',
            ],
        ],
        'models.calling-actions' => [
            'reason' => 'Invokes business logic from the model layer instead of from an Action, Job or Controller.',
            'entries' => [
                'App\Models\BackupFile',
                'App\Models\Metric',
                'App\Models\Server',
                'App\Models\ServerIpAddress',
                'App\Models\Service',
            ],
        ],
        'jobs.without-unique-queue' => [
            'reason' => 'Read-only polling or teardown work with no UniqueQueue lock, so concurrent runs are possible.',
            'entries' => [
                'App\Jobs\HostedDomain\CheckDomainJob',
                'App\Jobs\SSL\CheckSslExpiryJob',
                'App\Jobs\SSL\DeleteSiteSslJob',
            ],
        ],
        'jobs.without-failure-handler' => [
            'reason' => 'Has no failed() hook, so a failure leaves no trace on the owning record.',
            'entries' => [
                'App\Jobs\HostedDomain\CheckDomainJob',
                'App\Jobs\SSL\CheckSslExpiryJob',
                'App\Jobs\Server\CheckForUpdatesJob',
            ],
        ],
        'policies.user-owned' => [
            'reason' => 'Guards a user-owned resource rather than a project-scoped one, so project role checks do not apply.',
            'entries' => [
                'App\Policies\DNSProviderPolicy',
                'App\Policies\NotificationChannelPolicy',
                'App\Policies\PersonalAccessTokenPolicy',
                'App\Policies\ServerProviderPolicy',
                'App\Policies\ServerTemplatePolicy',
                'App\Policies\SourceControlPolicy',
                'App\Policies\SshKeyPolicy',
                'App\Policies\StorageProviderPolicy',
                'App\Policies\UserPolicy',
            ],
        ],
        'conventions.writes-without-bootstrap-bust' => [
            'reason' => 'Writes a bootstrap-backed model without calling GetBootstrap::forgetVersion(). BootPlugins, CheckForUpdates and InstallGithubPlugin only touch non-catalogue fields or delegate; DiscoverPlugins adds and removes plugins, so in production clients can keep a stale catalogue forever.',
            'entries' => [
                'BootPlugins.php',
                'DiscoverPlugins.php',
                'Github/CheckForUpdates.php',
                'Github/InstallGithubPlugin.php',
            ],
        ],
        'extension-points.non-tooling-classes' => [
            'reason' => 'Registry and state helpers that live in the tooling namespace without being a tool.',
            'entries' => [
                'App\Tooling\ToolingRegistry',
                'App\Tooling\SiteToolingState',
            ],
        ],
        'extension-points.non-channel-classes' => [
            'reason' => 'A Mailable that ships with the email channel rather than being a channel itself.',
            'entries' => [
                'App\NotificationChannels\Email\NotificationMail',
            ],
        ],
        'security.local-process' => [
            'reason' => 'Runs the Process component on the Vito host to install plugins, not against a managed server.',
            'entries' => [
                'App\Plugins\LegacyPlugins',
            ],
        ],
        'routing.public-endpoints' => [
            'reason' => 'Reachable without an authenticated session: the auth flow, public documentation, and machine callbacks that verify their own signature or shared secret inside the controller.',
            'entries' => [
                'api.yaml',
                'api/docs',
                'api/health',
                'api/git-hooks',
                'api/github-hooks',
                'api/servers/{server}/agent',
                'api/webhooks',
                'ziggy',
                'login',
                'logout',
                'register',
                'forgot-password',
                'reset-password',
                'verify-email',
                'email',
                'user/confirm-password',
                'user/confirmed-password-status',
                'two-factor-challenge',
                'invitations',
                'sanctum',
                'up',
                '_ignition',
                'horizon',
            ],
        ],
    ];

    /**
     * @return array<int, string>
     */
    public static function except(string $key): array
    {
        if (! array_key_exists($key, self::EXCEPTIONS)) {
            throw new InvalidArgumentException("Unknown architecture exception key [{$key}].");
        }

        return self::EXCEPTIONS[$key]['entries'];
    }
}
