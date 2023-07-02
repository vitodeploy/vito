<?php

namespace App\Http\Livewire\Profile;

use App\Actions\User\UpdateUserPassword;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class UpdatePassword extends Component
{
    public string $current_password;

    public string $password;

    public string $password_confirmation;

    public function update(): void
    {
        app(UpdateUserPassword::class)->update(auth()->user(), $this->all());

        $this->current_password = '';
        $this->password = '';
        $this->password_confirmation = '';

        session()->flash('status', 'password-updated');
    }

    public function render(): View
    {
        return view('livewire.profile.update-password');
    }
}
