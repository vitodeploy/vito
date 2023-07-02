<?php

namespace App\Http\Livewire\Profile;

use App\Actions\User\UpdateUserProfileInformation;
use App\Http\Livewire\UserDropdown;
use Exception;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class UpdateProfileInformation extends Component
{
    public string $name;

    public string $email;

    public function mount(): void
    {
        $this->name = auth()->user()->name;
        $this->email = auth()->user()->email;
    }

    /**
     * @throws Exception
     */
    public function submit(): void
    {
        app(UpdateUserProfileInformation::class)->update(auth()->user(), $this->all());

        session()->flash('status', 'profile-updated');

        $this->emitTo(UserDropdown::class, '$refresh');
    }

    public function sendVerificationEmail(): void
    {
        if (! auth()->user()->hasVerifiedEmail()) {
            auth()->user()->sendEmailVerificationNotification();

            session()->flash('status', 'verification-link-sent');
        }
    }

    public function render(): View
    {
        return view('livewire.profile.update-profile-information');
    }
}
