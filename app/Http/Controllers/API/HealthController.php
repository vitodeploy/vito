<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;

class HealthController extends Controller
{
    public function __invoke()
    {
        return response()->json([
            'success' => true,
            'version' => vito_version(),
        ]);
    }
}
