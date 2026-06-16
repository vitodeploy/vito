<?php

namespace App\SiteTypes;

use App\DTOs\DynamicField;
use App\Models\Deployment;
use App\Models\Site;
use App\Models\SourceControl;

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
        $shared = parent::sharedFormFields();

        return [
            array_shift($shared),
            DynamicField::make('use_source_control')
                ->checkbox()
                ->label('Add source control')
                ->description('Deploy this site from a Git repository.')
                ->default(false),
            ...$shared,
        ];
    }

    public function createRules(array $input): array
    {
        $rules = [
            'port' => ['required', 'integer', 'between:1024,65535'],
            'start_command' => ['nullable', 'string', 'max:255', 'not_regex:/[\r\n]/'],
        ];

        if (! empty($input['use_source_control'])) {
            $rules['source_control'] = SourceControl::siteValidationRules($this->site->server);
            $rules['repository'] = ['required'];
            $rules['branch'] = ['required'];
        }

        return $rules;
    }

    public function defaultDeploymentScript(): string
    {
        if (! $this->site->repository) {
            return '';
        }

        return parent::defaultDeploymentScript();
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
