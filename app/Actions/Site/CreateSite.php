<?php

namespace App\Actions\Site;

use App\Enums\SiteStatus;
use App\Exceptions\RepositoryNotFound;
use App\Exceptions\RepositoryPermissionDenied;
use App\Exceptions\SourceControlIsNotConnected;
use App\Facades\Notifier;
use App\Models\Server;
use App\Models\Site;
use App\Notifications\SiteInstallationFailed;
use App\Notifications\SiteInstallationSucceed;
use App\ValidationRules\DomainRule;
use Exception;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class CreateSite
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws Throwable
     */
    public function create(Server $server, array $input): Site
    {
        Validator::make($input, self::rules($server, $input))->validate();

        DB::beginTransaction();
        try {
            $user = $input['user'] ?? $server->getSshUser();
            $site = new Site([
                'server_id' => $server->id,
                'type' => $input['type'],
                'domain' => $input['domain'],
                'aliases' => $input['aliases'] ?? [],
                'port' => $input['port'] ?? null,
                'user' => $user,
                'path' => '/home/'.$user.'/'.$input['domain'],
                'status' => SiteStatus::INSTALLING,
            ]);

            // fields based on the type
            $site->fill($site->type()->createFields($input));

            // check has access to repository
            try {
                if ($site->sourceControl) {
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

            // create default deployment script
            $site->deploymentScript()->create([
                'name' => 'default',
                'content' => '',
            ]);

            // create base commands if any
            $site->commands()->createMany($site->type()->baseCommands());

            // install site
            dispatch(function () use ($site): void {
                $site->type()->install();
                $site->update([
                    'status' => SiteStatus::READY,
                    'progress' => 100,
                ]);
                Notifier::send($site, new SiteInstallationSucceed($site));
            })->catch(function () use ($site): void {
                $site->status = SiteStatus::INSTALLATION_FAILED;
                $site->save();
                Notifier::send($site, new SiteInstallationFailed($site));
            })->onConnection('ssh');

            DB::commit();

            return $site;
        } catch (Exception $e) {
            DB::rollBack();
            throw ValidationException::withMessages([
                'type' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function rules(Server $server, array $input): array
    {
        $rules = [
            'type' => [
                'required',
                Rule::in(config('core.site_types')),
            ],
            'domain' => [
                'required',
                new DomainRule,
                Rule::unique('sites', 'domain')->where(fn ($query) => $query->where('server_id', $server->id)),
            ],
            'aliases.*' => [
                new DomainRule,
            ],
            'user' => [
                'nullable',
                'regex:/^[a-z_][a-z0-9_-]*[a-z0-9]$/',
                'min:3',
                'max:32',
                Rule::unique('sites', 'user')->where('server_id', $server->id),
                Rule::notIn($server->getSshUsers()),
            ],
            'port' => [
                'nullable',
                'integer',
                'min:1',
                'max:65535',
                Rule::unique('sites', 'port')
                    ->where(fn (Builder $query) => $query->where('server_id', $server->id)),
            ],
        ];

        return array_merge($rules, self::typeRules($server, $input));
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, array<string>>
     */
    private static function typeRules(Server $server, array $input): array
    {
        if (! isset($input['type']) || ! in_array($input['type'], config('core.site_types'))) {
            return [];
        }

        $site = new Site([
            'server_id' => $server->id,
            'type' => $input['type']]
        );

        return $site->type()->createRules($input);
    }
}
