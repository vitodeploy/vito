<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('search')]
#[Middleware(['auth'])]
class SearchController extends Controller
{
    #[Get('/', name: 'search')]
    public function search(Request $request): JsonResponse
    {
        $this->validate($request, [
            'query' => 'required|string|min:3',
        ]);

        $query = $request->input('query');

        $results = [
            'data' => [], // Replace with actual search results
        ];

        return response()->json($results);
    }
}
