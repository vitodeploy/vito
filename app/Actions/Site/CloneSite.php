<?php

namespace App\Actions\Site;

use App\Enums\SiteStatus;
use App\Exceptions\RepositoryNotFound;
use App\Exceptions\RepositoryPermissionDenied;
use App\Exceptions\SourceControlIsNotConnected;
use App\Facades\Notifier;
use App\Models\Site;
use App\Notifications\SiteInstallationFailed;
use App\Notifications\SiteInstallationSucceed;
use App\ValidationRules\DomainRule;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CloneSite
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function clone(Site $sourceSite, array $input): Site
    {
        DB::beginTransaction();
        try {
            $server = $sourceSite->server;

            // Use provided username or fallback to source site's user
            $user = $input['user'] ?? $sourceSite->user;
            $path = '/home/'.$user.'/'.$input['domain'];

            $clonedSite = new Site([
                'server_id' => $server->id,
                'type' => $sourceSite->type,
                'domain' => $input['domain'],
                'aliases' => $input['aliases'] ?? [],
                'user' => $user,
                'path' => $path,
                'status' => SiteStatus::INSTALLING,
                'repository' => $sourceSite->repository,
                'branch' => $input['branch'] ?? $sourceSite->branch,
                'source_control_id' => $sourceSite->source_control_id,
            ]);

            // clone type data and add copied from site id
            $type_data = $sourceSite->type_data;
            $type_data['copied_from_site_id'] = (string) $sourceSite->id;
            $clonedSite->type_data = $type_data;

            // Check repository access
            try {
                if ($clonedSite->sourceControl) {
                    $clonedSite->sourceControl->getRepo($clonedSite->repository);
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

            // Save the cloned site
            $clonedSite->save();

            // clone deployment script
            $clonedSite->deploymentScript->update([
                'content' => $sourceSite->deploymentScript->content ?? '',
            ]);

            // clone commands
            $commands = $sourceSite->commands->map(fn ($command): array => [
                'name' => $command->name,
                'command' => $command->command,
            ])->toArray();

            $clonedSite->commands()->createMany($commands);

            // Setup cloned site
            dispatch(function () use ($clonedSite): void {
                if ($clonedSite->isIsolated()) {
                    $clonedSite->server->os()->createIsolatedUser(
                        $clonedSite->user,
                        Str::random(15),
                        $clonedSite->id
                    );
                }
                $clonedSite->type()->cloneSite();
                $clonedSite->update([
                    'status' => SiteStatus::READY,
                    'progress' => 100,
                ]);
                Notifier::send($clonedSite, new SiteInstallationSucceed($clonedSite));
            })->catch(function () use ($clonedSite): void {
                $clonedSite->status = SiteStatus::INSTALLATION_FAILED;
                $clonedSite->save();
                Notifier::send($clonedSite, new SiteInstallationFailed($clonedSite));
            })->onConnection('ssh');

            DB::commit();

            return $clonedSite;
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
    public static function rules(Site $sourceSite, array $input): array
    {
        $server = $sourceSite->server;

        return [
            'domain' => [
                'required',
                new DomainRule,
                Rule::unique('sites', 'domain')->where(fn ($query) => $query->where('server_id', $server->id)),
            ],
            'aliases.*' => [
                new DomainRule,
            ],
            'branch' => [
                'sometimes',
                'string',
            ],
            'user' => [
                'sometimes',
                'string',
                'regex:/^[a-z_][a-z0-9_-]*[$]?$/',
                Rule::unique('sites', 'user')->where(fn ($query) => $query->where('server_id', $server->id)),
            ],
        ];
    }
}
