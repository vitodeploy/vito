<?php

namespace App\Http\Controllers\Settings;

use App\Actions\ApiKey\CreateApiKey;
use App\Actions\ApiKey\RegenerateApiKey;
use Illuminate\Support\Facades\Route;
use App\Facades\Toast;
use App\Helpers\HtmxResponse;
use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ApiV1Controller extends Controller
{
    public function index(): View
    {
        $routes = Route::getRoutes();
        $apiRoutes = [];

        foreach($routes as $route) {
            if(in_array('application-api', $route->middleware())) {
                $apiRoutes[] = [
                    'label' => $route->uri() . " ({$route->getName()})",
                    'identifier' => $route->getName(),
                    'uri' => $route->uri(),
                    'method' => $route->methods()[0],
                    'name' => "permissions[{$route->getName()}]",
                ];
            }
        }

        return view('settings.api-v1.index', [
            'keys' => ApiKey::query()->latest()->get(),
            'endpoints' => $apiRoutes,
        ]);
    }

    public function store(Request $request): HtmxResponse
    {
        $apiKey = app(CreateApiKey::class)->create(
            $request->user(),
            $request->input()
        );

        Toast::success('API Key added');

        return htmx()->redirect(route('api-v1.index'));
    }

    public function update(Request $request, int $id)
    {
        $apiKey = ApiKey::query()->findOrFail($id);
        app(RegenerateApiKey::class)->regenerate($apiKey);

        Toast::success('API Key regenerated');

        return redirect()->route('api-v1.index');
    }

    public function destroy(int $id): RedirectResponse
    {
        $apiKey = ApiKey::query()->findOrFail($id);
        $apiKey->delete();

        Toast::success('API Key deleted');

        return redirect()->route('api-v1.index');
    }
}
