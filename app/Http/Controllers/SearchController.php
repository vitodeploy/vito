<?php

namespace App\Http\Controllers;

use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        $projects = DB::table('projects')
            ->select(
                DB::raw('projects.id as id'),
                DB::raw('null as parent_id'),
                DB::raw('projects.name as label'),
                DB::raw('"project" as type')
            )
            ->where(function (Builder $query) {
                if (! user()->isAdmin()) {
                    $query
                        ->join('user_project', 'projects.id', '=', 'user_project.project_id')
                        ->where('user_project.user_id', user()->id);
                }
            })
            ->where('projects.name', 'like', "%{$query}%");

        $servers = DB::table('servers')
            ->select(
                DB::raw('servers.id as id'),
                DB::raw('null as parent_id'),
                DB::raw('servers.name as label'),
                DB::raw('"server" as type')
            )
            ->join('projects', 'servers.project_id', '=', 'projects.id')
            ->where(function (Builder $query) {
                if (! user()->isAdmin()) {
                    $query
                        ->join('user_project', 'projects.id', '=', 'user_project.project_id')
                        ->where('user_project.user_id', user()->id);
                }
            })
            ->where('servers.name', 'like', "%{$query}%");

        $sites = DB::table('sites')
            ->select(
                DB::raw('sites.id as id'),
                DB::raw('sites.server_id as parent_id'),
                DB::raw('sites.domain as label'),
                DB::raw('"site" as type')
            )
            ->join('servers', 'sites.server_id', '=', 'servers.id')
            ->join('projects', 'servers.project_id', '=', 'projects.id')
            ->where(function (Builder $query) {
                if (! user()->isAdmin()) {
                    $query
                        ->join('user_project', 'projects.id', '=', 'user_project.project_id')
                        ->where('user_project.user_id', user()->id);
                }
            })
            ->where('sites.domain', 'like', "%{$query}%");

        // Combine with unionAll
        $results = $projects
            ->unionAll($servers)
            ->unionAll($sites)
            ->get();

        $results = [
            'data' => $results, // Replace with actual search results
        ];

        return response()->json($results);
    }
}
