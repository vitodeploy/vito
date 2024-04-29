<?php

namespace App\Http\Controllers;

use App\Actions\User\UpdateUserPassword;
use App\Actions\User\UpdateUserProfileInformation;
use App\Facades\Toast;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function index(): View
    {
        return view('profile.index');
    }

    public function info(Request $request): RedirectResponse
    {
        app(UpdateUserProfileInformation::class)->update(
            $request->user(),
            $request->input()
        );

        Toast::success('Profile information updated.');

        return back();
    }

    public function password(Request $request): RedirectResponse
    {
        app(UpdateUserPassword::class)->update(
            $request->user(),
            $request->input()
        );

        Toast::success('Password updated.');

        return back();
    }
}
