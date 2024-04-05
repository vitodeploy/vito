<?php

namespace App\Actions\Site;

use App\Exceptions\RepositoryNotFound;
use App\Exceptions\RepositoryPermissionDenied;
use App\Exceptions\SourceControlIsNotConnected;
use App\Models\Site;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UpdateSourceControl
{
    public function update(Site $site, array $input): void
    {
        $this->validate($input);

        $site->source_control_id = $input['source_control'];
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
        $site->save();
    }

    private function validate(array $input): void
    {
        Validator::make($input, [
            'source_control' => [
                'required',
                Rule::exists('source_controls', 'id'),
            ],
        ])->validate();
    }
}
