<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Tag\AttachTag;
use App\Actions\Tag\CreateTag;
use App\Actions\Tag\DeleteTag;
use App\Actions\Tag\DetachTag;
use App\Actions\Tag\EditTag;
use App\Facades\Toast;
use App\Helpers\HtmxResponse;
use App\Http\Controllers\Controller;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function index(Request $request): View
    {
        $data = [
            'tags' => Tag::getByProjectId(auth()->user()->current_project_id)->get(),
        ];

        if ($request->has('edit')) {
            $data['editTag'] = Tag::find($request->input('edit'));
        }

        return view('settings.tags.index', $data);
    }

    public function create(Request $request): HtmxResponse
    {
        /** @var User $user */
        $user = $request->user();

        app(CreateTag::class)->create(
            $user,
            $request->input(),
        );

        Toast::success('Tag created.');

        return htmx()->redirect(route('settings.tags'));
    }

    public function update(Tag $tag, Request $request): HtmxResponse
    {
        app(EditTag::class)->edit(
            $tag,
            $request->input(),
        );

        Toast::success('Tag updated.');

        return htmx()->redirect(route('settings.tags'));
    }

    public function attach(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        app(AttachTag::class)->attach($user, $request->input());

        return back()->with([
            'status' => 'tag-created',
        ]);
    }

    public function detach(Request $request, Tag $tag): RedirectResponse
    {
        app(DetachTag::class)->detach($tag, $request->input());

        return back()->with([
            'status' => 'tag-detached',
        ]);
    }

    public function delete(Tag $tag): RedirectResponse
    {
        app(DeleteTag::class)->delete($tag);

        Toast::success('Tag deleted.');

        return back();
    }
}
