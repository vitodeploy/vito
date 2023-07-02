<?php

namespace App\Actions\SourceControl;

use App\Models\SourceControl;
use Illuminate\Validation\ValidationException;

class ConnectSourceControl
{
    public function connect(string $provider, array $input): void
    {
        $sourceControl = SourceControl::query()
            ->where('provider', $provider)
            ->first();
        if (! $sourceControl) {
            $sourceControl = new SourceControl([
                'provider' => $provider,
            ]);
        }

        if (! $input['token']) {
            $sourceControl->delete();

            return;
        }

        $sourceControl->access_token = $input['token'];
        if (! $sourceControl->provider()->connect()) {
            throw ValidationException::withMessages([
                'token' => __('Cannot connect to :provider or invalid token!', ['provider' => $provider]),
            ]);
        }
        $sourceControl->save();
    }
}
