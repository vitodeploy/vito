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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CreateSite
{
    /**
     * @throws SourceControlIsNotConnected
     */
    public function create(Server $server, array $input): Site
    {
        $this->validateInputs($server, $input);

        DB::beginTransaction();
        try {
            $site = new Site([
                'server_id' => $server->id,
                'type' => $input['type'],
                'domain' => $input['domain'],
                'aliases' => $input['aliases'] ?? [],
                'path' => '/home/'.$server->getSshUser().'/'.$input['domain'],
                'status' => SiteStatus::INSTALLING,
            ]);

            // fields based on the type
            $site->fill($site->type()->createFields($input));

            // check has access to repository
            try {
                if ($site->sourceControl()) {
                    $site->sourceControl()->getRepo($site->repository);
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

            // validate type
            $this->validateType($site, $input);

            // set type data
            $site->type_data = $site->type()->data($input);

            // save
            $site->save();

            // create default deployment script
            $site->deploymentScript()->create([
                'name' => 'default',
                'content' => '',
            ]);

            // install site
            dispatch(function () use ($site) {
                $site->type()->install();
                $site->update([
                    'status' => SiteStatus::READY,
                    'progress' => 100,
                ]);
                Notifier::send($site, new SiteInstallationSucceed($site));
            })->catch(function () use ($site) {
                $site->status = SiteStatus::INSTALLATION_FAILED;
                $site->save();
                Notifier::send($site, new SiteInstallationFailed($site));
            })->onConnection('ssh');

            DB::commit();

            return $site;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * @throws ValidationException
     */
    private function validateInputs(Server $server, array $input): void
    {
        $rules = [
            'type' => [
                'required',
                Rule::in(config('core.site_types')),
            ],
            'domain' => [
                'required',
                new DomainRule(),
                Rule::unique('sites', 'domain')->where(function ($query) use ($server) {
                    return $query->where('server_id', $server->id);
                }),
            ],
            'aliases.*' => [
                new DomainRule(),
            ],
        ];

        Validator::make($input, $rules)->validate();
    }

    /**
     * @throws ValidationException
     */
    private function validateType(Site $site, array $input): void
    {
        $rules = $site->type()->createRules($input);

        Validator::make($input, $rules)->validate();
    }
}
