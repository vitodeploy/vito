<?php

namespace App\Http\Controllers;

use App\Actions\Site\Deploy;
use App\Actions\Site\UpdateBranch;
use App\Actions\Site\UpdateDeploymentScript;
use App\Actions\Site\UpdateEnv;
use App\Exceptions\DeploymentScriptIsEmptyException;
use App\Exceptions\RepositoryNotFound;
use App\Exceptions\RepositoryPermissionDenied;
use App\Exceptions\SourceControlIsNotConnected;
use App\Exceptions\SSHUploadFailed;
use App\Facades\Toast;
use App\Helpers\HtmxResponse;
use App\Models\Deployment;
use App\Models\Server;
use App\Models\Site;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    public function deploy(Server $server, Site $site): HtmxResponse
    {
        $this->authorize('manage', $server);

        try {
            app(Deploy::class)->run($site);

            Toast::success('Deployment started!');
        } catch (SourceControlIsNotConnected) {
            Toast::error('Source control is not connected. Check site\'s settings.');
        } catch (DeploymentScriptIsEmptyException) {
            Toast::error('Deployment script is empty!');
        } catch (RepositoryPermissionDenied) {
            Toast::error('You do not have permission to access this repository!');
        } catch (RepositoryNotFound) {
            Toast::error('Repository not found!');
        }

        return htmx()->back();
    }

    public function showDeploymentLog(Server $server, Site $site, Deployment $deployment): RedirectResponse
    {
        $this->authorize('manage', $server);

        return back()->with('content', $deployment->log?->getContent());
    }

    public function updateDeploymentScript(Server $server, Site $site, Request $request): RedirectResponse
    {
        $this->authorize('manage', $server);

        app(UpdateDeploymentScript::class)->update($site, $request->input());

        Toast::success('Deployment script updated!');

        return back();
    }

    public function updateBranch(Server $server, Site $site, Request $request): RedirectResponse
    {
        $this->authorize('manage', $server);

        app(UpdateBranch::class)->update($site, $request->input());

        Toast::success('Branch updated!');

        return back();
    }

    public function getEnv(Server $server, Site $site): RedirectResponse
    {
        $this->authorize('manage', $server);

        return back()->with('env', $site->getEnv());
    }

    public function updateEnv(Server $server, Site $site, Request $request): RedirectResponse
    {
        $this->authorize('manage', $server);

        try {
            app(UpdateEnv::class)->update($site, $request->input());
            Toast::success('Env updated!');
        } catch (SSHUploadFailed) {
            Toast::error('Failed to update .env file!');
        }

        return back();
    }

    public function enableAutoDeployment(Server $server, Site $site): HtmxResponse
    {
        $this->authorize('manage', $server);

        if (! $site->isAutoDeployment()) {
            try {
                $site->enableAutoDeployment();

                $site->refresh();

                Toast::success('Auto deployment has been enabled.');
            } catch (SourceControlIsNotConnected) {
                Toast::error('Source control is not connected. Check site\'s settings.');
            } catch (DeploymentScriptIsEmptyException) {
                Toast::error('Deployment script is empty!');
            } catch (RepositoryPermissionDenied) {
                Toast::error('You do not have permission to access this repository!');
            } catch (RepositoryNotFound) {
                Toast::error('Repository not found!');
            }
        }

        return htmx()->back();
    }

    public function disableAutoDeployment(Server $server, Site $site): HtmxResponse
    {
        $this->authorize('manage', $server);

        if ($site->isAutoDeployment()) {
            try {
                $site->disableAutoDeployment();

                $site->refresh();

                Toast::success('Auto deployment has been disabled.');
            } catch (SourceControlIsNotConnected) {
                Toast::error('Source control is not connected. Check site\'s settings.');
            }
        }

        return htmx()->back();
    }
}
