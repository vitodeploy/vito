<?php

namespace App\Web\Pages\Servers\FileManager\Widgets;

use App\Actions\FileManager\FetchFiles;
use App\Exceptions\SSHError;
use App\Models\File;
use App\Models\Server;
use App\Models\User;
use App\Web\Fields\CodeEditorField;
use App\Web\Pages\Servers\FileManager\Index;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Support\Enums\ActionSize;
use Filament\Support\Enums\IconPosition;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class FilesList extends Widget
{
    public Server $server;

    public string $serverUser;

    public string $path;

    /**
     * @var array<string>
     */
    protected $listeners = ['$refresh'];

    public function mount(): void
    {
        $this->serverUser = $this->server->ssh_user;
        $this->path = home_path($this->serverUser);
        if (request()->has('path') && request()->has('user')) {
            $this->path = request('path');
            $this->serverUser = request('user');
        }
        $this->refresh();
    }

    /**
     * @return array<int, mixed>
     */
    protected function getTableHeaderActions(): array
    {
        return [
            $this->homeAction(),
            $this->userAction(),
            ActionGroup::make([
                $this->refreshAction(),
                $this->newFileAction(),
                $this->newDirectoryAction(),
                $this->uploadAction(),
            ])
                ->tooltip('Toolbar')
                ->icon('heroicon-o-ellipsis-vertical')
                ->color('gray')
                ->size(ActionSize::Large)
                ->iconPosition(IconPosition::After)
                ->dropdownPlacement('bottom-end'),
        ];
    }

    /**
     * @return Builder<File>
     */
    protected function getTableQuery(): Builder
    {
        return File::query()
            ->where('user_id', auth()->id())
            ->where('server_id', $this->server->id);
    }

    public function table(Table $table): Table
    {
        auth()->user();

        return $table
            ->query($this->getTableQuery())
            ->headerActions($this->getTableHeaderActions())
            ->heading(str($this->path)->substr(-50)->start(str($this->path)->length() > 50 ? '...' : ''))
            ->columns([
                IconColumn::make('type')
                    ->sortable()
                    ->icon(fn (File $file): string => $this->getIcon($file)),
                TextColumn::make('name')
                    ->sortable(),
                TextColumn::make('size')
                    ->sortable(),
                TextColumn::make('owner')
                    ->sortable(),
                TextColumn::make('group')
                    ->sortable(),
                TextColumn::make('date')
                    ->sortable(),
                TextColumn::make('permissions')
                    ->sortable(),
            ])
            ->recordUrl(function (File $file): string {
                if ($file->type === 'directory') {
                    return Index::getUrl([
                        'server' => $this->server->id,
                        'user' => $file->server_user,
                        'path' => absolute_path($file->path.'/'.$file->name),
                    ]);
                }

                return '';
            })
            ->defaultSort('type')
            ->actions([
                $this->extractAction(),
                $this->downloadAction(),
                $this->editAction(),
                $this->deleteAction(),
            ])
            ->checkIfRecordIsSelectableUsing(
                fn (File $file): bool => $file->name !== '..',
            )
            ->bulkActions([
                DeleteBulkAction::make()
                    ->requiresConfirmation(),
            ]);
    }

    public function changeUser(string $user): void
    {
        $this->redirect(
            Index::getUrl([
                'server' => $this->server->id,
                'user' => $user,
                'path' => home_path($user),
            ]),
            true
        );
    }

    public function refresh(): void
    {
        /** @var User $user */
        $user = auth()->user();

        try {
            app(FetchFiles::class)->fetch(
                $user,
                $this->server,
                [
                    'user' => $this->serverUser,
                    'path' => $this->path,
                ]
            );
        } catch (SSHError) {
            abort(404);
        }
        $this->dispatch('$refresh');
    }

    protected function getIcon(File $file): string
    {
        if ($file->type === 'directory') {
            return 'heroicon-o-folder';
        }

        if (str($file->name)->endsWith('.blade.php')) {
            return 'laravel';
        }

        if (str($file->name)->endsWith('.php')) {
            return 'php';
        }

        return 'heroicon-o-document-text';
    }

    protected function homeAction(): Action
    {
        return Action::make('home')
            ->label('Home')
            ->size(ActionSize::Small)
            ->icon('heroicon-o-home')
            ->action(function (): void {
                $this->path = home_path($this->serverUser);
                $this->refresh();
            });
    }

    protected function userAction(): ActionGroup
    {
        $users = [];
        foreach ($this->server->getSshUsers() as $user) {
            $users[] = Action::make('user-'.$user)
                ->action(fn () => $this->changeUser($user))
                ->label($user);
        }

        return ActionGroup::make($users)
            ->tooltip('Change user')
            ->label($this->serverUser)
            ->button()
            ->size(ActionSize::Small)
            ->color('gray')
            ->icon('heroicon-o-chevron-up-down')
            ->iconPosition(IconPosition::After)
            ->dropdownPlacement('bottom-end');
    }

    protected function refreshAction(): Action
    {
        return Action::make('refresh')
            ->label('Refresh')
            ->icon('heroicon-o-arrow-path')
            ->action(fn () => $this->refresh());
    }

    protected function newFileAction(): Action
    {
        return Action::make('new-file')
            ->label('New File')
            ->icon('heroicon-o-document-text')
            ->action(function (array $data): void {
                run_action($this, function () use ($data): void {
                    $this->server->os()->write(
                        $this->path.'/'.$data['name'],
                        str_replace("\r\n", "\n", $data['content']),
                        $this->serverUser
                    );
                    $this->refresh();
                });
            })
            ->form(fn (): array => [
                TextInput::make('name')
                    ->placeholder('file-name.txt'),
                CodeEditorField::make('content'),
            ])
            ->modalSubmitActionLabel('Create')
            ->modalHeading('New File')
            ->modalWidth('4xl');
    }

    protected function newDirectoryAction(): Action
    {
        return Action::make('new-directory')
            ->label('New Directory')
            ->icon('heroicon-o-folder')
            ->action(function (array $data): void {
                run_action($this, function () use ($data): void {
                    $this->server->os()->mkdir(
                        $this->path.'/'.$data['name'],
                        $this->serverUser
                    );
                    $this->refresh();
                });
            })
            ->form(fn (): array => [
                TextInput::make('name')
                    ->placeholder('directory name'),
            ])
            ->modalSubmitActionLabel('Create')
            ->modalHeading('New Directory')
            ->modalWidth('lg');
    }

    protected function uploadAction(): Action
    {
        return Action::make('upload')
            ->label('Upload File')
            ->icon('heroicon-o-arrow-up-on-square')
            ->action(function (array $data): void {
                //
            })
            ->after(function (array $data): void {
                run_action($this, function () use ($data): void {
                    foreach ($data['file'] as $file) {
                        $this->server->ssh()->upload(
                            Storage::disk('tmp')->path($file),
                            $this->path.'/'.$file,
                            $this->serverUser
                        );
                    }
                    $this->refresh();
                });
            })
            ->form(fn (): array => [
                FileUpload::make('file')
                    ->disk('tmp')
                    ->multiple()
                    ->preserveFilenames(),
            ])
            ->modalSubmitActionLabel('Upload to Server')
            ->modalHeading('Upload File')
            ->modalWidth('xl');
    }

    protected function extractAction(): Action
    {
        return Action::make('extract')
            ->tooltip('Extract')
            ->icon('heroicon-o-archive-box')
            ->hiddenLabel()
            ->visible(fn (File $file): bool => $file->isExtractable())
            ->action(function (File $file): void {
                $file->server->os()->extract($file->getFilePath(), $file->path, $file->server_user);
                $this->refresh();
            });
    }

    protected function downloadAction(): Action
    {
        return Action::make('download')
            ->tooltip('Download')
            ->icon('heroicon-o-arrow-down-tray')
            ->hiddenLabel()
            ->visible(fn (File $file): bool => $file->type === 'file')
            ->action(function (File $file) {
                $file->server->ssh($file->server_user)->download(
                    Storage::disk('tmp')->path($file->name),
                    $file->getFilePath()
                );

                return Storage::disk('tmp')->download($file->name);
            });
    }

    protected function editAction(): Action
    {
        return Action::make('edit')
            ->tooltip('Edit')
            ->icon('heroicon-o-pencil')
            ->hiddenLabel()
            ->visible(fn (File $file): bool => $file->type === 'file')
            ->action(function (File $file, array $data): void {
                $file->server->os()->write(
                    $file->getFilePath(),
                    str_replace("\r\n", "\n", $data['content']),
                    $file->server_user
                );
                $this->refresh();
            })
            ->form(fn (File $file): array => [
                CodeEditorField::make('content')
                    ->formatStateUsing(function () use ($file) {
                        $file->server->ssh($file->server_user)->download(
                            Storage::disk('tmp')->path($file->name),
                            $file->getFilePath()
                        );

                        return Storage::disk('tmp')->get(basename($file->getFilePath()));
                    }),
            ])
            ->modalSubmitActionLabel('Save')
            ->modalHeading('Edit')
            ->modalWidth('4xl');
    }

    protected function deleteAction(): Action
    {
        return Action::make('delete')
            ->tooltip('Delete')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->hiddenLabel()
            ->requiresConfirmation()
            ->visible(fn (File $file): bool => $file->name !== '..')
            ->action(function (File $file): void {
                run_action($this, function () use ($file): void {
                    $file->delete();
                });
            });
    }
}
