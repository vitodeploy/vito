<?php

namespace App\Actions\Application;

use App\Enums\ApplicationStatus;
use App\Jobs\Application\CreateJob;
use App\Models\Application;
use App\Models\Server;
use App\ValidationRules\DomainRule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CreateApplication
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function create(Server $server, array $input): Application
    {
        $this->validate($server, $input);

        $application = new Application([
            'server_id' => $server->id,
            'type' => $input['type'],
            'domain' => $input['domain'],
            'aliases' => $input['aliases'] ?? [],
            'status' => ApplicationStatus::INSTALLING,
        ]);

        $application->type_data = $application->type()->data($input);
        $application->save();

        dispatch(new CreateJob($application))->onQueue('ssh');

        return $application;
    }

    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    private function validate(Server $server, array $input): void
    {
        $rules = [
            'type' => [
                'required',
                Rule::in(array_keys(config('application.types'))),
            ],
            'domain' => [
                'required',
                new DomainRule,
                Rule::unique('applications', 'domain')->where(fn ($query) => $query->where('server_id', $server->id)),
                Rule::unique('sites', 'domain')->where(fn ($query) => $query->where('server_id', $server->id)),
            ],
            'aliases.*' => [
                new DomainRule,
            ],
        ];

        Validator::make($input, array_merge($rules, $this->typeRules($server, $input)))->validate();
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function typeRules(Server $server, array $input): array
    {
        if (! isset($input['type']) || ! config('application.types.'.$input['type'])) {
            return [];
        }

        $application = new Application([
            'server_id' => $server->id,
            'type' => $input['type'],
        ]);

        return $application->type()->createRules($input);
    }
}
