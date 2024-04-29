<?php

namespace App\Http\Controllers\Settings;

use App\Actions\SshKey\CreateSshKey;
use App\Facades\Toast;
use App\Helpers\HtmxResponse;
use App\Http\Controllers\Controller;
use App\Models\SshKey;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SSHKeyController extends Controller
{
    public function index(): View
    {
        return view('settings.ssh-keys.index', [
            'keys' => SshKey::query()->latest()->get(),
        ]);
    }

    public function add(Request $request): HtmxResponse
    {
        app(CreateSshKey::class)->create(
            $request->user(),
            $request->input()
        );

        Toast::success('SSH Key added');

        return htmx()->redirect(route('settings.ssh-keys'));
    }

    public function delete(int $id): RedirectResponse
    {
        $key = SshKey::query()->findOrFail($id);

        $key->delete();

        Toast::success('SSH Key deleted');

        return redirect()->route('settings.ssh-keys');
    }
}
