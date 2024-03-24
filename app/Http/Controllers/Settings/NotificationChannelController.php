<?php

namespace App\Http\Controllers\Settings;

use App\Actions\NotificationChannels\AddChannel;
use App\Facades\Toast;
use App\Helpers\HtmxResponse;
use App\Http\Controllers\Controller;
use App\Models\NotificationChannel;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationChannelController extends Controller
{
    public function index(): View
    {
        return view('settings.notification-channels.index', [
            'channels' => NotificationChannel::query()->latest()->get(),
        ]);
    }

    public function add(Request $request): HtmxResponse
    {
        app(AddChannel::class)->add(
            $request->user(),
            $request->input()
        );

        Toast::success('Channel added successfully');

        return htmx()->redirect(route('notification-channels'));
    }

    public function delete(int $id): RedirectResponse
    {
        $channel = NotificationChannel::query()->findOrFail($id);

        $channel->delete();

        Toast::success('Channel deleted successfully');

        return redirect()->route('notification-channels');
    }
}
