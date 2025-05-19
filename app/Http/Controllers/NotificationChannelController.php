<?php

namespace App\Http\Controllers;

use App\Actions\NotificationChannels\AddChannel;
use App\Actions\NotificationChannels\EditChannel;
use App\Http\Resources\NotificationChannelResource;
use App\Models\NotificationChannel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Patch;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('settings/notification-channels')]
#[Middleware(['auth'])]
class NotificationChannelController extends Controller
{
    #[Get('/', name: 'notification-channels')]
    public function index(): Response
    {
        $this->authorize('viewAny', NotificationChannel::class);

        return Inertia::render('notification-channels/index', [
            'notificationChannels' => NotificationChannelResource::collection(NotificationChannel::getByProjectId(user()->current_project_id)->simplePaginate(config('web.pagination_size'))),
        ]);
    }

    #[Get('/json', name: 'notification-channels.json')]
    public function json(): ResourceCollection
    {
        $this->authorize('viewAny', NotificationChannel::class);

        return NotificationChannelResource::collection(NotificationChannel::getByProjectId(user()->current_project_id)->get());
    }

    #[Post('/', name: 'notification-channels.store')]
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', NotificationChannel::class);

        app(AddChannel::class)->add(user(), $request->all());

        return back()->with('success', 'Notification channel created.');
    }

    #[Patch('/{notificationChannel}', name: 'notification-channels.update')]
    public function update(Request $request, NotificationChannel $notificationChannel): RedirectResponse
    {
        $this->authorize('update', $notificationChannel);

        app(EditChannel::class)->edit($notificationChannel, user(), $request->all());

        return back()->with('success', 'Notification channel updated.');
    }

    #[Delete('{notificationChannel}', name: 'notification-channels.destroy')]
    public function destroy(NotificationChannel $notificationChannel): RedirectResponse
    {
        $this->authorize('delete', $notificationChannel);

        $notificationChannel->delete();

        return to_route('notification-channels')->with('success', 'Notification channel deleted.');
    }
}
