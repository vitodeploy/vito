<?php

namespace App\SiteFeatures\ModernDeployment;

use App\DTOs\DynamicField;
use App\DTOs\DynamicForm;
use App\Exceptions\SSHError;
use App\Helpers\SSH;
use App\SiteFeatures\Action;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class Enable extends Action
{
    protected SSH $ssh;

    public function name(): string
    {
        return 'Enable';
    }

    public function active(): bool
    {
        return ! $this->site->modernDeploymentEnabled();
    }

    public function form(): DynamicForm
    {
        return DynamicForm::make([
            DynamicField::make('alert')
                ->alert()
                ->options(['type' => 'warning'])
                ->description('While the feature is in beta, it is recommended to enable it on new websites than a live production site!'),
            DynamicField::make('alert-2')
                ->alert()
                ->options(['type' => 'warning'])
                ->link('Documentation', 'https://vitodeploy.com/docs/sites/modern-deployment')
                ->description("Read the documentation first to see how Modern Deployment works. Enabling Modern Deployment will change your site's path."),
            DynamicField::make('alert-3')
                ->alert()
                ->options(['type' => 'warning'])
                ->description("If you have any workers, you need to delete it before enabling the modern deployment as your site's path will change and you need to create the workers with the new path."),
            DynamicField::make('shared_resources')
                ->text()
                ->label('Shared resouces')
                ->default('.env,storage')
                ->description('Comma separated list of resources to be shared between deployments'),
            DynamicField::make('history')
                ->text()
                ->label('Deployments to keep')
                ->default('10')
                ->description('This amount specifies how many previous deployments to keep for rollback purposes'),
        ]);
    }

    /**
     * @throws SSHError
     */
    public function handle(Request $request): void
    {
        $this->validate($request);

        $this->ssh = $this->site->server->ssh($this->site->user);

        $sharedResources = explode(',', trim($request->input('shared_resources')));

        $this->ssh->exec(view('ssh.modern-deployment.enable', [
            'site' => $this->site,
            'sharedResources' => $sharedResources,
        ]), 'enable-modern-deployment', $this->site->id);

        $typeData = $this->site->type_data;
        $typeData['modern_deployment'] = true;
        $typeData['modern_deployment_shared_resources'] = $sharedResources;
        $typeData['modern_deployment_history'] = (int) $request->input('history');
        unset($typeData['env_path']);
        $this->site->type_data = $typeData;
        $this->site->path = $this->site->path.'/current';
        $this->site->save();

        $this->site->ensureDeploymentScriptsExist();

        $this->site->webserver()->updateVHost($this->site);

        $request->session()->flash('success', 'Modern deployment enabled!');
    }

    private function validate(Request $request): void
    {
        Validator::make($request->all(), [
            'shared_resources' => ['string', 'nullable'],
            'history' => ['required', 'integer', 'min:1'],
        ])->validate();
    }
}
