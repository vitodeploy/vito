<?php

namespace App\SiteFeatures\ModernDeployment;

use App\DTOs\DynamicField;
use App\DTOs\DynamicForm;
use App\Exceptions\SSHError;
use App\Helpers\SSH;
use App\SiteFeatures\Action;
use Illuminate\Http\Request;

class Disable extends Action
{
    protected SSH $ssh;

    public function name(): string
    {
        return 'Disable';
    }

    public function active(): bool
    {
        return $this->site->modernDeploymentEnabled();
    }

    public function form(): DynamicForm
    {
        return DynamicForm::make([
            DynamicField::make('alert')
                ->alert()
                ->options(['type' => 'warning'])
                ->description('Disabling modern deployment will remove all releases and keep the current active one as default.'),
        ]);
    }

    /**
     * @throws SSHError
     */
    public function handle(Request $request): void
    {
        $this->ssh = $this->site->server->ssh($this->site->user);

        $sharedResources = data_get($this->site->type_data, 'modern_deployment_shared_resources', []);

        $this->ssh->exec(view('ssh.modern-deployment.disable', [
            'site' => $this->site,
            'sharedResources' => $sharedResources,
        ]), 'disable-modern-deployment', $this->site->id);

        $typeData = $this->site->type_data;
        unset($typeData['modern_deployment']);
        unset($typeData['modern_deployment_shared_resources']);
        unset($typeData['modern_deployment_history']);
        unset($typeData['env_path']);
        $this->site->type_data = $typeData;
        $this->site->path = $this->site->basePath();
        $this->site->save();

        $this->site->webserver()->updateVHost($this->site);

        // set releases to null as they are already removed
        $this->site->deployments()->update(['release' => null]);

        $request->session()->flash('success', 'Modern deployment disabled!');
    }
}
