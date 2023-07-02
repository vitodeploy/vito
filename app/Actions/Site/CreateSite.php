<?php

namespace App\Actions\Site;

use App\Enums\SiteStatus;
use App\Exceptions\SourceControlIsNotConnected;
use App\Models\Server;
use App\Models\Site;
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
     * @throws ValidationException
     */
    public function create(Server $server, array $input): Site
    {
        $this->validateInputs($server, $input);

        try {
            DB::beginTransaction();

            $site = new Site([
                'server_id' => $server->id,
                'type' => $input['type'],
                'domain' => $input['domain'],
                'aliases' => isset($input['alias']) ? [$input['alias']] : [],
                'path' => '/home/'.$server->ssh_user.'/'.$input['domain'],
                'status' => SiteStatus::INSTALLING,
            ]);

            // fields based on type
            $site->fill($site->type()->createFields($input));

            // check has access to repository
            try {
                if ($site->sourceControl()) {
                    $site->sourceControl()->getRepo($site->repository);
                }
            } catch (SourceControlIsNotConnected) {
                throw ValidationException::withMessages([
                    'source_control' => __('Source control is not connected'),
                ]);
            }

            // detect php version
            if ($site->type()->language() === 'php') {
                $site->php_version = $input['php_version'];
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

            // install server
            $site->install();

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
            'alias' => [
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
        $rules = $site->type()->createValidationRules($input);

        Validator::make($input, $rules)->validate();
    }
}
