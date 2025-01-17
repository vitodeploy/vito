<?php

namespace App\Web\Pages\Settings\Projects\Widgets;

use App\Models\Project;
use App\Models\User;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;

class AddUser extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'components.form';

    public Project $project;

    public ?int $user;

    public function mount(Project $project): void
    {
        $this->project = $project;
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
                                ->whereNotExists(function ($query) {
                                    $query->select('user_id')
                                        ->from('user_project')
                                        ->whereColumn('users.id', 'user_project.user_id')
                                        ->where('user_project.project_id', $this->project->id);
                                })
                                ->pluck('name', 'id')
                            )
                            ->searchable()
                            ->rules(\App\Actions\Projects\AddUser::rules($this->project)['user']),
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
        $this->authorize('update', $this->project);

        $this->validate();

        app(\App\Actions\Projects\AddUser::class)
            ->add($this->project, [
                'user' => $this->user,
            ]);

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
