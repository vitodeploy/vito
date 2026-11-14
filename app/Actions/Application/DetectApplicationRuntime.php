<?php

namespace App\Actions\Application;

use App\Models\Application;
use App\Models\Project;
use App\Models\User;
use App\SourceControlProviders\SupportsRepositoryInspection;
use App\Support\RepositoryFiles;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Throwable;

class DetectApplicationRuntime
{
    /**
     * Suggests a branch and runtime for a repository. Detection only prefills the form, so a
     * provider failure returns no suggestion instead of blocking the user.
     *
     * @param  array<string, mixed>  $input
     * @return array{branch: ?string, runtime: ?string, versions: list<string>}
     */
    public function detect(Project $project, array $input, User $actor): array
    {
        Validator::make($input, [
            'branch' => ['nullable', 'string', 'max:255', 'not_regex:/[\x00-\x20\x7f]/', 'not_regex:/\A-/'],
        ])->validate();
        $provider = app(ValidateApplication::class)->provider($project, $input, $actor);
        $repository = $input['repository'];
        $branch = $input['branch'] ?? null;
        if (! $provider instanceof SupportsRepositoryInspection) {
            return ['branch' => $branch, 'runtime' => null, 'versions' => []];
        }

        try {
            $branch ??= $provider->getDefaultBranch($repository);
            if ($branch === null) {
                return ['branch' => null, 'runtime' => null, 'versions' => []];
            }
            $files = new RepositoryFiles(fn (string $path): ?string => $provider->getFileContents($repository, $branch, $path));
            foreach (array_reverse(array_keys(config('component.runtimes', []))) as $runtime) {
                $versions = (new Application(['runtime' => $runtime]))->preset()->detect($files);
                if ($versions !== null) {
                    return ['branch' => $branch, 'runtime' => $runtime, 'versions' => $versions];
                }
            }
        } catch (Throwable $e) {
            Log::warning('Application runtime detection failed', ['source_control_id' => $input['source_control_id'], 'exception' => $e::class]);
        }

        return ['branch' => $branch, 'runtime' => null, 'versions' => []];
    }
}
