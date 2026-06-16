<?php

namespace App\SiteTypes;

use App\DTOs\DynamicField;
use App\Models\Deployment;
use App\Models\Site;

class Blank extends AbstractProxiedSiteType
{
    public static function id(): string
    {
        return 'blank';
    }

    public function language(): string
    {
        return 'blank';
    }

    public static function make(): self
    {
        return new self(new Site(['type' => self::id()]));
    }

    /**
     * @return array<int, DynamicField>
     */
    public static function formFields(): array
    {
        return parent::sharedFormFields();
    }

    public function data(array $input): array
    {
        return [
            'start_command' => ! empty($input['start_command']) ? $input['start_command'] : '',
        ];
    }

    public function afterDeploy(Deployment $deployment): void
    {
        if ($this->startCommand() === '') {
            return;
        }

        parent::afterDeploy($deployment);
    }

    protected function defaultStartCommand(): string
    {
        return '';
    }
}
