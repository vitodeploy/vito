<?php

namespace App\Http\Controllers\Settings;

use App\Actions\NotificationChannels\AddChannel;
use App\Actions\NotificationChannels\EditChannel;
use App\Facades\Toast;
use App\Helpers\HtmxResponse;
use App\Http\Controllers\Controller;
use App\Models\NotificationChannel;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationChannelController extends Controller
{
    public function index(Request $request): View
    {
        $data = [
            'channels' => NotificationChannel::getByProjectId(auth()->user()->current_project_id)->get(),
        ];

        if ($request->has('edit')) {
            $data['editChannel'] = NotificationChannel::find($request->input('edit'));
        }

        return view('settings.notification-channels.index', $data);
    }

    public function add(Request $request): HtmxResponse
    {
        app(AddChannel::class)->add(
            $request->user(),
            $request->input()
        );

        Toast::success('Channel added successfully');

        return htmx()->redirect(route('settings.notification-channels'));
    }

    public function update(NotificationChannel $notificationChannel, Request $request): HtmxResponse
    {
        app(EditChannel::class)->edit(
            $notificationChannel,
            $request->user(),
            $request->input(),
        );

        Toast::success('Channel updated.');

        return htmx()->redirect(route('settings.notification-channels'));
    }

    public function delete(int $id): RedirectResponse
    {
        $channel = NotificationChannel::query()->findOrFail($id);

        $channel->delete();

        Toast::success('Channel deleted successfully');

        return redirect()->route('settings.notification-channels');
    }
}
