<?php

namespace App\Web\Pages\Servers\Sites;

use App\Actions\Site\Deploy;
use App\Actions\Site\UpdateBranch;
use App\Actions\Site\UpdateDeploymentScript;
use App\Actions\Site\UpdateEnv;
use App\Enums\SiteFeature;
use App\Models\ServerLog;
use App\Web\Fields\CodeEditorField;
use App\Web\Pages\Servers\Logs\Widgets\LogsList;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Enums\MaxWidth;
use Livewire\Attributes\On;

class View extends Page
{
    protected static ?string $slug = 'servers/{server}/sites/{site}';

    protected static ?string $title = 'Application';

    public string $previousStatus;

    public function mount(): void
    {
        $this->previousStatus = $this->site->status;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view', [static::getSiteFromRoute(), static::getServerFromRoute()]) ?? false;
    }

    #[On('$refresh')]
    public function refresh(): void
    {
        $currentStatus = $this->site->refresh()->status;

        if ($this->previousStatus !== $currentStatus) {
            $this->redirect(static::getUrl(parameters: [
                'server' => $this->server,
                'site' => $this->site,
            ]));
        }

        $this->previousStatus = $currentStatus;
    }

    public function getWidgets(): array
    {
        $widgets = [];

        if ($this->site->isInstalling()) {
            $widgets[] = [Widgets\Installing::class, ['site' => $this->site]];
            if (auth()->user()->can('viewAny', [ServerLog::class, $this->server])) {
                $widgets[] = [
                    LogsList::class, [
                        'server' => $this->server,
                        'site' => $this->site,
                        'label' => 'Logs',
                    ],
                ];
            }
        }

        if ($this->site->isReady()) {
            if (in_array(SiteFeature::DEPLOYMENT, $this->site->type()->supportedFeatures())) {
                $widgets[] = [Widgets\DeploymentsList::class, ['site' => $this->site]];
            }
        }

        return $widgets;
    }

    public function getHeaderActions(): array
    {
        $actions = [];
        $actionsGroup = [];

        if (in_array(SiteFeature::DEPLOYMENT, $this->site->type()->supportedFeatures())) {
            $actions[] = $this->deployAction();
            $actionsGroup[] = $this->deploymentScriptAction();
        }

        if (in_array(SiteFeature::ENV, $this->site->type()->supportedFeatures())) {
            $actionsGroup[] = $this->dotEnvAction();
        }

        $actionsGroup[] = $this->branchAction();

        $actions[] = ActionGroup::make($actionsGroup)
            ->button()
            ->color('gray')
            ->icon('heroicon-o-chevron-up-down')
            ->iconPosition(IconPosition::After)
            ->dropdownPlacement('bottom-end');

        return $actions;
    }

    public function getSecondSubNavigation(): array
    {
        if ($this->site->isInstalling()) {
            return [];
        }

        return parent::getSecondSubNavigation();
    }

    private function deployAction(): Action
    {
        return Action::make('deploy')
            ->icon('heroicon-o-rocket-launch')
            ->action(function () {
                run_action($this, function () {
                    app(Deploy::class)->run($this->site);

                    Notification::make()
                        ->success()
                        ->title('Deployment started!')
                        ->send();

                    $this->dispatch('$refresh');
                });
            });
    }

    private function deploymentScriptAction(): Action
    {
        return Action::make('deployment-script')
            ->label('Deployment Script')
            ->modalSubmitActionLabel('Save')
            ->modalHeading('Update Deployment Script')
            ->form([
                CodeEditorField::make('script')
                    ->default($this->site->deploymentScript?->content)
                    ->rules(UpdateDeploymentScript::rules()['script']),
            ])
            ->action(function (array $data) {
                run_action($this, function () use ($data) {
                    app(UpdateDeploymentScript::class)->update($this->site, $data);

                    Notification::make()
                        ->success()
                        ->title('Deployment script updated!')
                        ->send();
                });
            });
    }

    private function dotEnvAction(): Action
    {
        return Action::make('dot-env')
            ->label('Update .env')
            ->modalSubmitActionLabel('Save')
            ->modalHeading('Update .env file')
            ->form([
                CodeEditorField::make('env')
                    ->formatStateUsing(function () {
                        return $this->site->getEnv();
                    })
                    ->rules([
                        'env' => 'required',
                    ]),
            ])
            ->action(function (array $data) {
                run_action($this, function () use ($data) {
                    app(UpdateEnv::class)->update($this->site, $data);

                    Notification::make()
                        ->success()
                        ->title('.env updated!')
                        ->send();
                });
            });
    }

    private function branchAction(): Action
    {
        return Action::make('branch')
            ->label('Branch')
            ->modalSubmitActionLabel('Save')
            ->modalHeading('Change branch')
            ->modalWidth(MaxWidth::Medium)
            ->form([
                TextInput::make('branch')
                    ->default($this->site->branch)
                    ->rules(UpdateBranch::rules()['branch']),
            ])
            ->action(function (array $data) {
                run_action($this, function () use ($data) {
                    app(UpdateBranch::class)->update($this->site, $data);

                    Notification::make()
                        ->success()
                        ->title('Branch updated!')
                        ->send();
                });
            });
    }
}
