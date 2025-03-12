<?php

namespace App\Web\Pages\Servers\Sites;

use App\Actions\Site\DeleteSite;
use App\Web\Fields\CodeEditorField;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;

class Settings extends Page
{
    protected static ?string $slug = 'servers/{server}/sites/{site}/settings';

    protected static ?string $title = 'Settings';

    /**
     * @var array<string>
     */
    protected $listeners = ['$refresh'];

    public function mount(): void
    {
        $this->authorize('update', [$this->site, $this->server]);
    }

    public function getWidgets(): array
    {
        return [
            [Widgets\SiteDetails::class, ['site' => $this->site]],
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->vhostAction(),
            $this->deleteAction(),
        ];
    }

    private function deleteAction(): Action
    {
        return DeleteAction::make()
            ->icon('heroicon-o-trash')
            ->record($this->site)
            ->modalHeading('Delete Site')
            ->modalDescription('Once your site is deleted, all of its resources and data will be permanently deleted and can\'t be restored')
            ->using(function (): void {
                run_action($this, function (): void {
                    app(DeleteSite::class)->delete($this->site);

                    $this->redirect(Index::getUrl(['server' => $this->server]));
                });
            });
    }

    private function vhostAction(): Action
    {
        return Action::make('vhost')
            ->color('gray')
            ->icon('icon-nginx')
            ->label('VHost')
            ->modalSubmitActionLabel('Save')
            ->form([
                CodeEditorField::make('vhost')
                    ->formatStateUsing(fn (): string => $this->site->webserver()->getVhost($this->site))
                    ->rules(['required']),
            ])
            ->action(function (array $data): void {
                run_action($this, function () use ($data): void {
                    $this->site->webserver()->updateVHost($this->site, $data['vhost']);
                    Notification::make()
                        ->success()
                        ->title('VHost updated!')
                        ->send();
                });
            });
    }
}
