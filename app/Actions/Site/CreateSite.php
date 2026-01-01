<?php

namespace App\Actions\Site;

use App\Enums\SiteStatus;
use App\Exceptions\RepositoryNotFound;
use App\Exceptions\RepositoryPermissionDenied;
use App\Exceptions\SourceControlIsNotConnected;
use App\Jobs\Site\CreateJob;
use App\Models\Server;
use App\Models\Site;
use App\ValidationRules\DomainRule;
use Exception;
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
        $this->validate($server, $input);

        DB::beginTransaction();
        try {
            $user = $input['user'];
            $site = new Site([
                'server_id' => $server->id,
                'type' => $input['type'],
                'domain' => $input['domain'],
                'aliases' => $input['aliases'] ?? [],
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

            // create base commands if any
            $site->commands()->createMany($site->type()->baseCommands());

            // install site
            dispatch(new CreateJob($site))->onQueue('ssh');

            DB::commit();

            return $site;
        } catch (Exception $e) {
            DB::rollBack();
            throw ValidationException::withMessages([
                'type' => $e->getMessage(),
            ]);
        }
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
                Rule::unique('sites', 'domain')->where(fn ($query) => $query->where('server_id', $server->id)),
            ],
            'aliases.*' => [
                new DomainRule,
            ],
            'user' => [
                'required',
                'regex:/^[a-z_][a-z0-9_-]*[a-z0-9]$/',
                'min:3',
                'max:32',
                Rule::unique('sites', 'user')->where('server_id', $server->id),
                Rule::notIn($server->getSshUsers()),
            ],
        ];

        Validator::make($input, array_merge($rules, $this->typeRules($server, $input)))->validate();
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
}
