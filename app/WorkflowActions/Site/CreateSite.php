<?php

namespace App\WorkflowActions\Site;

use App\DTOs\DynamicField;
use App\Models\Server;
use App\Models\Site;
use App\WorkflowActions\AbstractWorkflowAction;
use Illuminate\Support\Facades\Validator;

abstract class CreateSite extends AbstractWorkflowAction
{
    protected function siteType(): ?string
    {
        return null;
    }

    public function inputs(): array
    {
        return [
            DynamicField::make('server_id')->resource('server')->label('Server'),
            DynamicField::make('domain')->text()->label('Domain')->placeholder('example.com'),
            DynamicField::make('aliases')->tags()->label('Aliases')->default([])->placeholder('www.example.com'),
            DynamicField::make('user')->text()->label('Isolated User')->placeholder('example')->description('Linux user the site runs as (lowercase, 3-32 characters)'),
        ];
    }

    public function outputs(): array
    {
        return [
            'site_id' => 'The ID of the created site',
            'site_domain' => 'The domain of the created site',
            'site_path' => 'The path of the created site on the server',
            'site_status' => 'The status of the created site',
        ];
    }

    public function run(array $input): array
    {
        Validator::make($input, [
            'server_id' => ['required', 'integer', 'exists:servers,id'],
        ])->validate();

        /** @var Server $server */
        $server = Server::query()->findOrFail($input['server_id']);

        $this->authorize('create', [Site::class, $server]);

        $site = app(\App\Actions\Site\CreateSite::class)->create(
            $server,
            [...$input, 'type' => $this->siteType() ?? ($input['type'] ?? null)],
            $this->user,
        );

        return [
            'site_id' => $site->id,
            'site_domain' => $site->domain,
            'site_path' => $site->path,
            'site_status' => $site->status->value,
        ];
    }

    protected function phpVersionField(): DynamicField
    {
        return DynamicField::make('php_version')->text()->label('PHP Version')->placeholder('8.4');
    }

    protected function webDirectoryField(): DynamicField
    {
        return DynamicField::make('web_directory')->text()->label('Web Directory')->placeholder('public')->description('Leave empty for the site root');
    }

    /**
     * @return array<int, DynamicField>
     */
    protected function repositoryFields(): array
    {
        return [
            DynamicField::make('source_control')->resource('source_control')->label('Source Control'),
            DynamicField::make('repository')->text()->label('Repository')->placeholder('organization/repository'),
            DynamicField::make('branch')->text()->label('Branch')->placeholder('main'),
        ];
    }
}
