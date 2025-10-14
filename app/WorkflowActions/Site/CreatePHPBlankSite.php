<?php

namespace App\WorkflowActions\Site;

class CreatePHPBlankSite extends CreateSite
{
    public function inputs(): array
    {
        return array_merge(parent::inputs(), [
            'type' => 'php-blank',
            'php_version' => 'PHP version, example: 8.1, 8.2',
            'web_directory' => 'Web directory, example: public (optional)',
        ]);
    }
}
