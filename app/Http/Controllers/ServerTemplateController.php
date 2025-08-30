<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;

#[Prefix('server-templates')]
#[Middleware(['auth'])]
class ServerTemplateController extends Controller
{
    #[Get('/', name: 'server-templates.index')]
    public function index(): JsonResponse
    {
        return response()->json([
            'templates' => user()->serverTemplates,
        ]);
    }

    #[Post('/', name: 'server-templates.store')]
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'services' => 'required|array',
        ]);

        user()->serverTemplates()->create($data);

        return back()->with('success', __('Server template created successfully.'));
    }

    #[Put('/{id}', name: 'server-templates.update')]
    public function update(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'services' => 'sometimes|required|array',
        ]);

        $template = user()->serverTemplates()->findOrFail($id);
        $template->update($data);

        return back()->with('success', __('Server template updated successfully.'));
    }

    #[Delete('/{id}', name: 'server-templates.destroy')]
    public function destroy(int $id): RedirectResponse
    {
        $template = user()->serverTemplates()->findOrFail($id);
        $template->delete();

        return back()->with('success', __('Server template deleted successfully.'));
    }
}
