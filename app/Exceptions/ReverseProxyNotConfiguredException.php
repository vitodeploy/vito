<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ReverseProxyNotConfiguredException extends Exception
{
    public function render(Request $request): RedirectResponse
    {
        $message = $this->getMessage() !== ''
            ? $this->getMessage()
            : 'Please set a port and a start command before deploying this site.';

        if ($request->header('X-Inertia')) {
            return back()->with('error', $message);
        }

        throw ValidationException::withMessages([
            'deployment' => $message,
        ]);
    }
}
