<?php

namespace App\Web\Pages\Servers\Sites\Widgets;

use App\Models\Site;
use App\Models\User;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Validation\Rule;

class AddUser extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'components.form';

    public Site $site;

    public ?int $user = null;

    public function mount(Site $site): void
    {
        $this->site = $site;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->heading('Add User')
                    ->schema([
                        Select::make('user')
                            ->name('user')
                            ->options(fn () => User::query()
                                ->whereNotExists(function ($query): void {
                                    $query->select('user_id')
                                        ->from('site_user')
                                        ->whereColumn('users.id', 'site_user.user_id')
                                        ->where('site_user.site_id', $this->site->id);
                                })
                                ->pluck('name', 'id')
                            )
                            ->searchable()
                            ->rules([
                                'required',
                                Rule::exists('users', 'id'),
                                Rule::unique('site_user', 'user_id')->where('site_id', $this->site->id),
                            ]),
                    ])
                    ->footerActions([
                        Action::make('add')
                            ->label('Add')
                            ->action(fn () => $this->submit()),
                    ]),
            ]);
    }

    public function submit(): void
    {
        // تغيير التصريح لاستخدام سياسة addUser بدلاً من update
        $this->authorize('addUser', [$this->site, $this->site->server]);

        $this->validate();

        $this->site->users()->attach($this->user);

        Notification::make()
            ->title('User added!')
            ->success()
            ->send();

        $this->user = null;
    }

    public function updated(): void
    {
        $this->dispatch('userAdded');
    }
}