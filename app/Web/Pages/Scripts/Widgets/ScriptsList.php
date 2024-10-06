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
    protected $listeners = ['$refresh'];

    protected function getTableQuery(): Builder
    {
        return Script::getByProjectId(auth()->user()->current_project_id, auth()->user()->id);
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
                ->color(fn ($record) => $record->project_id ? 'gray' : 'success')
                ->formatStateUsing(function (Script $record) {
                    return $record->project_id ? 'No' : 'Yes';
                }),
            TextColumn::make('created_at')
                ->label('Created At')
                ->formatStateUsing(fn (Script $record) => $record->created_at_by_timezone)
                ->searchable()
                ->sortable(),
        ];
    }

    public function getTable(): Table
    {
        return $this->table
            ->heading('')
            ->recordUrl(fn (Script $record) => Executions::getUrl(['script' => $record]))
            ->actions([
                EditAction::make('edit')
                    ->label('Edit')
                    ->modalHeading('Edit Script')
                    ->mutateRecordDataUsing(function (array $data, Script $record) {
                        return [
                            'name' => $record->name,
                            'content' => $record->content,
                            'global' => $record->project_id === null,
                        ];
                    })
                    ->form([
                        TextInput::make('name')
                            ->rules(EditScript::rules()['name']),
                        CodeEditorField::make('content')
                            ->rules(EditScript::rules()['content'])
                            ->helperText('You can use variables like ${VARIABLE_NAME} in the script. The variables will be asked when executing the script'),
                        Checkbox::make('global')
                            ->label('Is Global (Accessible in all projects)'),
                    ])
                    ->authorize(fn (Script $record) => auth()->user()->can('update', $record))
                    ->using(function (array $data, Script $record) {
                        app(EditScript::class)->edit($record, auth()->user(), $data);
                        $this->dispatch('$refresh');
                    })
                    ->modalWidth(MaxWidth::ThreeExtraLarge),
                DeleteAction::make('delete')
                    ->label('Delete')
                    ->modalHeading('Delete Script')
                    ->authorize(fn (Script $record) => auth()->user()->can('delete', $record))
                    ->using(function (array $data, Script $record) {
                        $record->delete();
                    }),
            ]);
    }
}
