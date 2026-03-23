<?php

namespace App\Http\Controllers;

use App\Actions\Site\CreateCommand;
use App\Actions\Site\EditCommand;
use App\Actions\Site\ExecuteCommand;
use App\Http\Resources\CommandExecutionResource;
use App\Http\Resources\CommandResource;
use App\Models\Command;
use App\Models\Server;
use App\Models\Site;
use App\Tables\CommandTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;

#[Prefix('/servers/{server}/sites/{site}/commands')]
#[Middleware(['auth', 'has-project'])]
class CommandController extends Controller
{
    #[Get('/', name: 'commands')]
    public function index(Server $server, Site $site): Response
    {
        $this->authorize('viewAny', [Command::class, $site, $server]);

        return Inertia::render('commands/index', [
            'commands' => CommandTable::make($site->commands()->with('site'))->toInertia(),
        ]);
    }

    #[Post('/', name: 'commands.store')]
    public function store(Request $request, Server $server, Site $site): RedirectResponse
    {
        $this->authorize('create', [Command::class, $site, $server]);

        app(CreateCommand::class)->create($site, $request->input());

        return back()
            ->with('success', 'Command created successfully.');
    }

    #[Get('/{command}', name: 'commands.show')]
    public function show(Server $server, Site $site, Command $command): Response
    {
        $this->authorize('view', [$command, $site, $server]);

        return Inertia::render('commands/show', [
            'command' => new CommandResource($command),
            'executions' => CommandExecutionResource::collection($command->executions()->latest()->simplePaginate(config('web.pagination_size'))),
        ]);
    }

    #[Put('/{command}', name: 'commands.update')]
    public function update(Request $request, Server $server, Site $site, Command $command): RedirectResponse
    {
        $this->authorize('update', [$command, $site, $server]);

        app(EditCommand::class)->edit($command, $request->input());

        return back()
            ->with('success', 'Command updated successfully.');
    }

    #[Delete('/{command}', name: 'commands.destroy')]
    public function destroy(Server $server, Site $site, Command $command): RedirectResponse
    {
        $this->authorize('delete', [$command, $site, $server]);

        $command->delete();

        return back()
            ->with('success', 'Command deleted successfully.');
    }

    #[Post('/{command}/execute', name: 'commands.execute')]
    public function execute(Request $request, Server $server, Site $site, Command $command): RedirectResponse
    {
        $this->authorize('update', [$site, $server]);

        app(ExecuteCommand::class)->execute($command, user(), $request->input());

        return redirect()->route('commands.show', ['server' => $server, 'site' => $site, 'command' => $command])
            ->with('info', 'Command is being executed.');
    }
}
