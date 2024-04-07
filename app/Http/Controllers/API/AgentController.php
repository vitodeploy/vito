<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class AgentController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json();
    }
}
