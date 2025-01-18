<?php

namespace App\Web\Pages\Servers\Sites\Widgets;

use App\Actions\Site\UpdateAliases;
use App\Actions\Site\UpdatePHPVersion;
use App\Actions\Site\UpdateSourceControl;
use App\Models\Site;
use App\Models\SourceControl;
use App\Web\Pages\Settings\SourceControls\Actions\Create;
use App\Web\Pages\Settings\Tags\Actions\EditTags;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Components\Actions\Action;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Support\Enums\MaxWidth;
use Filament\Widgets\Widget;

class SiteDetails extends Widget implements HasForms, HasInfolists
{
    use InteractsWithForms;
    use InteractsWithInfolists;

    protected $listeners = ['$refresh'];

    protected static bool $isLazy = false;

    protected static string $view = 'components.infolist';

    public Site $site;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make()
                    ->heading('Site Details')
                    ->description('More details about your site')
                    ->columns(1)
                    ->schema([
                        TextEntry::make('id')
                            ->label('ID')
                            ->inlineLabel()
                            ->hintIcon('heroicon-o-information-circle')
                            ->hintIconTooltip('Site unique identifier to use in the API'),
                        TextEntry::make('user')
                            ->label('Site User')
                            ->inlineLabel(),
                        TextEntry::make('created_at')
                            ->label('Created At')
                            ->formatStateUsing(fn ($record) => $record->created_at_by_timezone)
                            ->inlineLabel(),
                        TextEntry::make('type')
                            ->extraAttributes(['class' => 'capitalize'])
                            ->icon(fn ($state) => 'icon-'.$state)
                            ->inlineLabel(),
                        TextEntry::make('tags.*')
                            ->default('No tags')
                            ->formatStateUsing(fn ($state) => is_object($state) ? $state->name : $state)
                            ->inlineLabel()
                            ->badge()
                            ->color(fn ($state) => is_object($state) ? $state->color : 'gray')
                            ->icon(fn ($state) => is_object($state) ? 'heroicon-o-tag' : '')
                            ->suffixAction(
                                EditTags::infolist($this->site)
                            ),
                        TextEntry::make('php_version')
                            ->label('PHP Version')
                            ->inlineLabel()
                            ->suffixAction(
                                Action::make('edit_php_version')
                                    ->icon('heroicon-o-pencil-square')
                                    ->tooltip('Change')
                                    ->modalSubmitActionLabel('Save')
                                    ->modalHeading('Update PHP Version')
                                    ->modalWidth(MaxWidth::Medium)
                                    ->form([
                                        Select::make('version')
                                            ->label('Version')
                                            ->selectablePlaceholder(false)
                                            ->rules(UpdatePHPVersion::rules($this->site)['version'])
                                            ->default($this->site->php_version)
                                            ->options(
                                                collect($this->site->server->installedPHPVersions())
                                                    ->mapWithKeys(fn ($version) => [$version => $version])
                                            ),

                                    ])
                                    ->action(function (array $data) {
                                        run_action($this, function () use ($data) {
                                            app(UpdatePHPVersion::class)->update($this->site, $data);

                                            Notification::make()
                                                ->success()
                                                ->title('PHP version updated!')
                                                ->send();
                                        });
                                    })
                            ),
                        TextEntry::make('aliases.*')
                            ->inlineLabel()
                            ->badge()
                            ->default('No aliases')
                            ->color(fn ($state) => $state == 'No aliases' ? 'gray' : 'primary')
                            ->suffixAction(
                                Action::make('edit_aliases')
                                    ->icon('heroicon-o-pencil-square')
                                    ->tooltip('Change')
                                    ->modalSubmitActionLabel('Save')
                                    ->modalHeading('Update Aliases')
                                    ->modalWidth(MaxWidth::Medium)
                                    ->form([
                                        TagsInput::make('aliases')
                                            ->splitKeys(['Enter', 'Tab', ' ', ','])
                                            ->placeholder('Type and press enter to add an alias')
                                            ->default($this->site->aliases)
                                            ->nestedRecursiveRules(UpdateAliases::rules()['aliases.*']),

                                    ])
                                    ->action(function (array $data) {
                                        run_action($this, function () use ($data) {
                                            app(UpdateAliases::class)->update($this->site, $data);

                                            Notification::make()
                                                ->success()
                                                ->title('Aliases updated!')
                                                ->send();
                                        });
                                    })
                            ),
                        TextEntry::make('source_control_id')
                            ->label('Source Control')
                            ->visible(fn (Site $record) => $record->sourceControl)
                            ->formatStateUsing(fn (Site $record) => $record->sourceControl?->profile)
                            ->inlineLabel()
                            ->suffixAction(
                                Action::make('edit_source_control')
                                    ->icon('heroicon-o-pencil-square')
                                    ->tooltip('Change')
                                    ->modalSubmitActionLabel('Save')
                                    ->modalHeading('Update Source Control')
                                    ->modalWidth(MaxWidth::Medium)
                                    ->form([
                                        Select::make('source_control')
                                            ->label('Source Control')
                                            ->rules(UpdateSourceControl::rules()['source_control'])
                                            ->options(
                                                SourceControl::getByProjectId(auth()->user()->current_project_id)
                                                    ->pluck('profile', 'id')
                                            )
                                            ->default($this->site->source_control_id)
                                            ->suffixAction(
                                                \Filament\Forms\Components\Actions\Action::make('connect')
                                                    ->form(Create::form())
                                                    ->modalHeading('Connect to a source control')
                                                    ->modalSubmitActionLabel('Connect')
                                                    ->icon('heroicon-o-wifi')
                                                    ->tooltip('Connect to a source control')
                                                    ->modalWidth(MaxWidth::Large)
                                                    ->authorize(fn () => auth()->user()->can('create', SourceControl::class))
                                                    ->action(fn (array $data) => Create::action($data))
                                            )
                                            ->placeholder('Select source control'),
                                    ])
                                    ->action(function (array $data) {
                                        run_action($this, function () use ($data) {
                                            app(UpdateSourceControl::class)->update($this->site, $data);

                                            Notification::make()
                                                ->success()
                                                ->title('Source control updated!')
                                                ->send();
                                        });
                                    })
                            ),
                    ]),
            ])
            ->record($this->site);
    }
}
