<?php

namespace App\SiteFeatures\ModernDeployment;

use App\Actions\Site\Deploy;
use App\Exceptions\SSHError;
use App\Helpers\SSH;
use App\SiteFeatures\Action;
use Illuminate\Http\Request;

class Enable extends Action
{
    protected SSH $ssh;

    public function name(): string
    {
        return 'Enable';
    }

    public function active(): bool
    {
        return ! data_get($this->site->type_data, 'modern_deployment', false);
    }

    /**
     * @throws SSHError
     */
    public function handle(Request $request): void
    {
        $this->ssh = $this->site->server->ssh($this->site->user);

        $sharedResources = explode(',', trim($request->input('shared_resources')));

        $this->ssh->exec(view('ssh.modern-deployment.enable', [
            'site' => $this->site,
            'sharedResources' => $sharedResources,
        ]), 'enable-modern-deployment', $this->site->id);

        $typeData = $this->site->type_data;
        $typeData['modern_deployment'] = true;
        $typeData['modern_deployment_shared_resources'] = $sharedResources;
        $this->site->type_data = $typeData;
        $this->site->path = $this->site->path.'/current';
        $this->site->save();

        $this->site->deploymentScripts()->where('name', 'build')->firstOrCreate(
            ['name' => 'build'],
            ['content' => '']
        );
        $this->site->deploymentScripts()->where('name', 'migrations')->firstOrCreate(
            ['name' => 'migrations'],
            ['content' => '']
        );
        $this->site->deploymentScripts()->where('name', 'post')->firstOrCreate(
            ['name' => 'post'],
            ['content' => '']
        );

        $this->site->webserver()->updateVHost($this->site, regenerate: ['core']);

        app(Deploy::class)->run($this->site, false);
    }
}
