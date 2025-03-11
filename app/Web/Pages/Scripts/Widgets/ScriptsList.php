<?php

namespace App\Web\Pages\Scripts\Widgets;

use App\Actions\Script\EditScript;
use App\Models\Script;
use App\Web\Fields\CodeEditorField;
use App\Web\Pages\Scripts\Executions;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as Widget;
use Illuminate\Database\Eloquent\Builder;

class ScriptsList extends Widget
{
    /**
     * @var array<string>
     */
    protected $listeners = ['$refresh'];

    /**
     * @return Builder<Script>
     */
    protected function getTableQuery(): Builder
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        throw_if($user->current_project_id === null);

        return Script::getByProjectId($user->current_project_id, $user->id);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('name')
                ->searchable()
                ->sortable(),
            TextColumn::make('id')
                ->label('Global')
                ->badge()
                ->color(fn ($record): string => $record->project_id ? 'gray' : 'success')
                ->formatStateUsing(fn (Script $record): string => $record->project_id ? 'No' : 'Yes'),
            TextColumn::make('created_at')
                ->label('Created At')
                ->formatStateUsing(fn (Script $record) => $record->created_at_by_timezone)
                ->searchable()
                ->sortable(),
        ];
    }

    public function table(Table $table): Table
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        return $table
            ->heading(null)
            ->query($this->getTableQuery())
            ->columns($this->getTableColumns())
            ->recordUrl(fn (Script $record): string => Executions::getUrl(['script' => $record]))
            ->actions([
                EditAction::make('edit')
                    ->label('Edit')
                    ->modalHeading('Edit Script')
                    ->mutateRecordDataUsing(fn (array $data, Script $record): array => [
                        'name' => $record->name,
                        'content' => $record->content,
                        'global' => $record->project_id === null,
                    ])
                    ->form([
                        TextInput::make('name')
                            ->rules(EditScript::rules()['name']),
                        CodeEditorField::make('content')
                            ->rules(EditScript::rules()['content'])
                            ->helperText('You can use variables like ${VARIABLE_NAME} in the script. The variables will be asked when executing the script'),
                        Checkbox::make('global')
                            ->label('Is Global (Accessible in all projects)'),
                    ])
                    ->authorize(fn (Script $record) => $user->can('update', $record))
                    ->using(function (array $data, Script $record) use ($user): void {
                        app(EditScript::class)->edit($record, $user, $data);
                        $this->dispatch('$refresh');
                    })
                    ->modalWidth(MaxWidth::ThreeExtraLarge),
                DeleteAction::make('delete')
                    ->label('Delete')
                    ->modalHeading('Delete Script')
                    ->authorize(fn (Script $record) => $user->can('delete', $record))
                    ->using(function (array $data, Script $record): void {
                        $record->delete();
                    }),
            ]);
    }
}
