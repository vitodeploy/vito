<?php

namespace App\Http\Controllers;

use App\Actions\Tag\CreateTag;
use App\Actions\Tag\DeleteTag;
use App\Actions\Tag\EditTag;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Patch;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('settings/tags')]
#[Middleware(['auth'])]
class TagController extends Controller
{
    #[Get('/', name: 'tags')]
    public function index(): Response
    {
        $this->authorize('viewAny', Tag::class);

        return Inertia::render('tags/index', [
            'tags' => TagResource::collection(Tag::getByProjectId(user()->current_project_id)->simplePaginate(config('web.pagination_size'))),
        ]);
    }

    #[Post('/', name: 'tags.store')]
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Tag::class);

        app(CreateTag::class)->create(user(), $request->input());

        return back()->with('success', 'Tag created.');
    }

    #[Patch('/{tag}', name: 'tags.update')]
    public function update(Request $request, Tag $tag): RedirectResponse
    {
        $this->authorize('update', $tag);

        app(EditTag::class)->edit($tag, $request->input());

        return back()->with('success', 'Tag updated.');
    }

    #[Delete('/{tag}', name: 'tags.destroy')]
    public function destroy(Tag $tag): RedirectResponse
    {
        $this->authorize('delete', $tag);

        app(DeleteTag::class)->delete($tag);

        return back()->with('success', 'Tag deleted.');
    }
}
