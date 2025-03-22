<?php

namespace App\Web\Pages\Servers\Sites;

use App\Actions\Site\Deploy;
use App\Actions\Site\DuplicateSite;
use App\Actions\Site\UpdateBranch;
use App\Actions\Site\UpdateDeploymentScript;
use App\Actions\Site\UpdateEnv;
use App\Enums\SiteFeature;
use App\Enums\SiteType;
use App\Facades\Notifier;
use App\Notifications\SiteDuplicationSucceed;
use App\ValidationRules\DomainRule;
use App\Web\Fields\CodeEditorField;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;

class View extends Page
{
    protected static ?string $slug = 'servers/{server}/sites/{site}';

    protected static ?string $title = 'Application';

    public string $previousStatus;

    public function mount(): void
    {
        $this->authorize('view', [$this->site, $this->server]);

        $this->previousStatus = $this->site->status;
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
        }

        if ($this->site->isReady()) {
            if (in_array(SiteFeature::COMMANDS, $this->site->type()->supportedFeatures())) {
                $widgets[] = [Widgets\Commands::class, ['site' => $this->site]];
            }

            if (in_array(SiteFeature::DEPLOYMENT, $this->site->type()->supportedFeatures())) {
                $widgets[] = [Widgets\DeploymentsList::class, ['site' => $this->site]];
            }

            if ($this->site->type === SiteType::LOAD_BALANCER) {
                $widgets[] = [Widgets\LoadBalancerServers::class, ['site' => $this->site]];
            }
        }

        return $widgets;
    }

    public function getHeaderActions(): array
    {
        $actions = [
            Action::make('read-the-docs')
                ->label('Read the Docs')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->url('https://vitodeploy.com/sites/application')
                ->openUrlInNewTab(),
        ];
        $actionsGroup = [];

        if (in_array(SiteFeature::DEPLOYMENT, $this->site->type()->supportedFeatures())) {
            $actions[] = $this->deployAction();
            if ($this->site->sourceControl) {
                $actionsGroup[] = $this->autoDeploymentAction();
            }
            $actionsGroup[] = $this->deploymentScriptAction();
        }

        if (in_array(SiteFeature::ENV, $this->site->type()->supportedFeatures())) {
            $actionsGroup[] = $this->dotEnvAction();
        }

        if ($this->site->sourceControl) {
            $actionsGroup[] = $this->branchAction();
        }

        if (in_array(SiteFeature::DUPLICATION, $this->site->type()->supportedFeatures())) {
            $actionsGroup[] = $this->duplicateSiteAction();
        }

        $actions[] = ActionGroup::make($actionsGroup)
            ->button()
            ->color('gray')
            ->icon('heroicon-o-chevron-up-down')
            ->iconPosition(IconPosition::After)
            ->dropdownPlacement('bottom-end');

        return $actions;
    }

    private function deployAction(): Action
    {
        return Action::make('deploy')
            ->icon('heroicon-o-rocket-launch')
            ->action(function (): void {
                if (! $this->site->deploymentScript?->content) {
                    Notification::make()
                        ->danger()
                        ->title('Deployment script is not set!')
                        ->send();

                    return;
                }
                run_action($this, function (): void {
                    app(Deploy::class)->run($this->site);

                    Notification::make()
                        ->success()
                        ->title('Deployment started!')
                        ->send();

                    $this->dispatch('$refresh');
                });
            });
    }

    private function autoDeploymentAction(): Action
    {
        return Action::make('auto-deployment')
            ->label(fn (): string => $this->site->isAutoDeployment() ? 'Disable Auto Deployment' : 'Enable Auto Deployment')
            ->modalHeading(fn (): string => $this->site->isAutoDeployment() ? 'Disable Auto Deployment' : 'Enable Auto Deployment')
            ->modalIconColor(fn (): string => $this->site->isAutoDeployment() ? 'red' : 'green')
            ->requiresConfirmation()
            ->action(function (): void {
                run_action($this, function (): void {
                    $this->site->isAutoDeployment()
                        ? $this->site->disableAutoDeployment()
                        : $this->site->enableAutoDeployment();

                    Notification::make()
                        ->success()
                        ->title('Auto deployment '.($this->site->isAutoDeployment() ? 'disabled' : 'enabled').'!')
                        ->send();
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
            ->action(function (array $data): void {
                run_action($this, function () use ($data): void {
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
                    ->formatStateUsing(fn (): string => $this->site->getEnv())
                    ->rules([
                        'env' => 'required',
                    ]),
            ])
            ->action(function (array $data): void {
                run_action($this, function () use ($data): void {
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
            ->action(function (array $data): void {
                run_action($this, function () use ($data): void {
                    app(UpdateBranch::class)->update($this->site, $data);

                    Notification::make()
                        ->success()
                        ->title('Branch updated!')
                        ->send();
                });
            });
    }

    private function duplicateSiteAction(): Action
    {
        return Action::make('duplicate-site')
            ->label('Duplicate Site')
            ->modalSubmitActionLabel('Duplicate')
            ->modalHeading('Duplicate Site')
            ->modalWidth(MaxWidth::Medium)
            ->form([
                TextInput::make('domain')
                    ->label('Domain')
                    ->placeholder('example.com')
                    ->required()
                    ->rules([
                        'required', new DomainRule,
                        Rule::unique('sites', 'domain')->where(fn ($query) => $query->where('server_id', $this->server->id)),
                    ]),

                TagsInput::make('aliases')
                    ->label('Aliases')
                    ->splitKeys(['Enter', 'Tab', ' ', ','])
                    ->placeholder('Type and press enter to add an alias')
                    ->helperText('Comma separated domains')
                    ->nestedRecursiveRules([
                        new DomainRule,
                    ]),

                TextInput::make('branch')
                    ->label('Branch')
                    ->default($this->site->branch)
                    ->visible(fn () => $this->site->sourceControl !== null)
                    ->helperText('Leave empty to use the same branch as the original site')
                    ->rules(['nullable', 'string']),
            ])
            ->action(function (array $data): void {
                run_action($this, function () use ($data): void {
                    $duplicatedSite = app(DuplicateSite::class)->duplicate($this->site, $data);

                    Notifier::send($duplicatedSite, new SiteDuplicationSucceed($duplicatedSite));

                    $this->redirect(static::getUrl(parameters: [
                        'server' => $this->server,
                        'site' => $duplicatedSite,
                    ]));
                });
            });
    }
}
