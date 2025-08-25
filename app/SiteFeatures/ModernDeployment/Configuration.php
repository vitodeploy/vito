<?php

namespace App\SiteFeatures\ModernDeployment;

use App\DTOs\DynamicField;
use App\DTOs\DynamicForm;
use App\Exceptions\SSHError;
use App\SiteFeatures\Action;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class Configuration extends Action
{
    public function name(): string
    {
        return 'Configuration';
    }

    public function active(): bool
    {
        return data_get($this->site->type_data, 'modern_deployment', false);
    }

    public function form(): DynamicForm
    {
        return DynamicForm::make([
            DynamicField::make('shared_resources')
                ->text()
                ->label('Shared resouces')
                ->default(implode(',', $this->site->type_data['modern_deployment_shared_resources'] ?? ['.env', 'storage']))
                ->description('Comma separated list of resources to be shared between deployments'),
            DynamicField::make('history')
                ->text()
                ->label('Deployments to keep')
                ->default($this->site->type_data['modern_deployment_history'] ?? '10')
                ->description('This amount specifies how many previous deployments to keep for rollback purposes'),
        ]);
    }

    /**
     * @throws SSHError
     */
    public function handle(Request $request): void
    {
        $this->validate($request);

        $sharedResources = explode(',', trim($request->input('shared_resources')));

        $typeData = $this->site->type_data;
        $typeData['modern_deployment_shared_resources'] = $sharedResources;
        $typeData['modern_deployment_history'] = (int) $request->input('history');
        $this->site->type_data = $typeData;
        $this->site->save();

        $request->session()->flash('success', 'Changes saved successfully.');
    }

    private function validate(Request $request): void
    {
        Validator::make($request->all(), [
            'shared_resources' => ['string', 'nullable'],
            'history' => ['required', 'integer', 'min:1'],
        ])->validate();
    }
}
