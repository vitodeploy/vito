<?php

namespace App\Actions\SourceControl;

use App\Models\SourceControl;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class EditSourceControl
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function edit(SourceControl $sourceControl, array $input): SourceControl
    {
        Validator::make($input, [
            'name' => [
                'required',
            ],
            'url' => [
                'nullable',
                'url:http,https',
                'ends_with:/',
            ],
            'port' => 'nullable|integer',
        ])->validate();

        $sourceControl->profile = $input['name'];
        $sourceControl->url = isset($input['url']) && $input['url'] ? $input['url'] : null;
        $sourceControl->port = isset($input['port']) && $input['port'] ? (int) $input['port'] : null;
        $sourceControl->project_id = isset($input['global']) && $input['global'] ? null : $sourceControl->user->currentProject?->id;

        $sourceControl->save();

        return $sourceControl;
    }
}
