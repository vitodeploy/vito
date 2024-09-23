<?php

namespace App\Web\Resources\Site\Pages;

use App\Actions\Server\CreateServer as CreateServerAction;
use App\Web\Fields\AlertField;
use App\Web\Resources\Site\SiteResource;
use App\Web\Traits\HasServerInfoWidget;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Throwable;

class CreateSite extends CreateRecord
{
    use HasServerInfoWidget;

    protected static string $resource = SiteResource::class;

    protected static bool $canCreateAnother = false;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('read-the-docs')
                ->label('Read the Docs')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->url('https://vitodeploy.com/sites/create-site.html')
                ->openUrlInNewTab(),
        ];
    }

    public function form(Form $form): Form
    {
        if (! session()->has('current_server_id')) {
            return $form->schema([
                AlertField::make('no-server-selected')
                    ->warning()
                    ->message('Select a server from the left sidebar first!'),
            ])->columns(1);
        }

        return $form->schema([

        ])->columns(1);
    }

    protected function getCreateFormAction(): \Filament\Actions\Action
    {
        $action = parent::getCreateFormAction();
        if (! session()->has('current_server_id')) {
            return $action->disabled();
        }

        return $action
            ->submit(null)
            ->action(function (array $data) {
                $this->authorize('create', static::getModel());

                $this->validate();

                try {
                    //                    app(CreateServerAction::class)->create(auth()->user(), $data);

                    $this->redirect(ListSites::getUrl());
                } catch (Throwable $e) {
                    Notification::make()
                        ->title($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }
}
