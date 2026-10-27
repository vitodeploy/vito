<?php

namespace App\Actions\EnvironmentAttachment;

use App\Models\Environment;
use App\Models\EnvironmentAttachment;
use App\Models\Service;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AttachRedis
{
    /** @param array<string, mixed> $input */
    public function attach(Environment $environment, array $input): EnvironmentAttachment
    {
        Validator::make($input, [
            'service_id' => ['required', 'integer', Rule::exists('services', 'id')->whereIn('name', ['redis', 'valkey'])
                ->whereIn('server_id', $environment->application->project->servers()->pluck('id')->all())],
        ])->validate();
        $service = Service::query()->findOrFail($input['service_id']);

        return app(AttachEnvironmentResource::class)->attach($environment, $service, $input, fn (): Service => $service);
    }
}
