<?php

namespace App\Web\Pages\Settings\Profile\Widgets;

use App\Actions\User\UpdateUserProfileInformation;
use App\Models\User;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;

class ProfileInformation extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static bool $isLazy = false;

    protected static string $view = 'components.form';

    public string $name;

    public string $email;

    public string $timezone;

    public function mount(): void
    {
        /** @var User $user */
        $user = auth()->user();
        $this->name = $user->name;
        $this->email = $user->email;
        $this->timezone = $user->timezone;
    }

    public function form(Form $form): Form
    {
        /** @var User $user */
        $user = auth()->user();

        $rules = UpdateUserProfileInformation::rules($user);

        return $form
            ->schema([
                Section::make()
                    ->heading('Profile Information')
                    ->description('Update your account\'s profile information and email address.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Name')
                            ->rules($rules['name']),
                        TextInput::make('email')
                            ->label('Email')
                            ->rules($rules['email']),
                        Select::make('timezone')
                            ->label('Timezone')
                            ->searchable()
                            ->options(
                                collect(timezone_identifiers_list())
                                    ->mapWithKeys(fn ($timezone) => [$timezone => $timezone])
                            )
                            ->rules($rules['timezone']),
                    ])
                    ->footerActions([
                        Action::make('save')
                            ->label('Save')
                            ->action(fn () => $this->submit()),
                    ]),
            ]);
    }

    public function submit(): void
    {
        /** @var User $user */
        $user = auth()->user();

        $this->validate();

        app(UpdateUserProfileInformation::class)->update($user, $this->all());

        Notification::make()
            ->success()
            ->title('Profile updated!')
            ->send();
    }
}
