<?php

namespace App\Web\Fields;

use Filament\Forms\Components\Field;

class CodeEditorField extends Field
{
    protected string $view = 'fields.code-editor';

    public string $lang = '';

    public bool $readonly = false;

    public function getOptions(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'lang' => $this->lang,
            'value' => json_encode($this->getState() ?? ''),
        ];
    }
}
