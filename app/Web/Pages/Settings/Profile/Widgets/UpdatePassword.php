<?php

namespace App\Web\Pages\Settings\Profile\Widgets;

use App\Actions\User\UpdateUserPassword;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;

class UpdatePassword extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static bool $isLazy = false;

    protected static string $view = 'components.form';

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function getFormSchema(): array
    {
        $rules = UpdateUserPassword::rules();

        return [
            Section::make()
                ->heading('Update Password')
                ->description('Ensure your account is using a long, random password to stay secure.')
                ->schema([
                    TextInput::make('current_password')
                        ->label('Current Password')
                        ->password()
                        ->rules($rules['current_password']),
                    TextInput::make('password')
                        ->label('New Password')
                        ->password()
                        ->rules($rules['password']),
                    TextInput::make('password_confirmation')
                        ->label('Confirm Password')
                        ->password()
                        ->rules($rules['password_confirmation']),
                ])
                ->footerActions([
                    Action::make('save')
                        ->label('Save')
                        ->action(fn () => $this->submit()),
                ]),
        ];
    }

    public function submit(): void
    {
        $this->validate();

        app(UpdateUserPassword::class)->update(auth()->user(), $this->all());

        $this->current_password = '';
        $this->password = '';
        $this->password_confirmation = '';

        Notification::make()
            ->success()
            ->title('Password updated!')
            ->send();
    }
}
