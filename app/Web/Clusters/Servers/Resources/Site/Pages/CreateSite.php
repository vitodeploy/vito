<?php

namespace App\Web\Clusters\Servers\Resources\Site\Pages;

use App\Web\Clusters\Servers\Resources\Site\SiteResource;
use App\Web\Traits\PageHasCluster;
use App\Web\Traits\PageHasServerInfoWidget;
use Filament\Actions\Action;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Throwable;

class CreateSite extends CreateRecord
{
    use PageHasCluster;
    use PageHasServerInfoWidget;

    protected static string $resource = SiteResource::class;

    protected static bool $canCreateAnother = false;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('read-the-docs')
                ->label('Read the Docs')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->url('https://vitodeploy.com/sites/create-site.html')
                ->openUrlInNewTab(),
        ];
    }

    public function form(Form $form): Form
    {
        return $form->schema([

        ])->columns(1);
    }

    protected function getCreateFormAction(): Action
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
