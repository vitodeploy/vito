<?php

namespace App\Jobs\Script;

use App\Events\Broadcast;
use App\Jobs\Job;
use App\Models\Script;
use App\Models\ScriptExecution;
use App\Models\Server;
use Throwable;

class ExecuteOn extends Job
{
    protected Script $script;

    protected Server $server;

    protected string $user;

    protected ScriptExecution $scriptExecution;

    public function __construct(Script $script, Server $server, string $user)
    {
        $this->script = $script;
        $this->server = $server;
        $this->user = $user;
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        $this->scriptExecution = $this->script->executions()->create([
            'server_id' => $this->server->id,
            'user' => $this->user,
        ]);
        $this->server->ssh($this->scriptExecution->user)->exec(
            $this->script->content,
            'execute-script'
        );
        $this->scriptExecution->finished_at = now();
        $this->scriptExecution->save();
        event(
            new Broadcast('execute-script-finished', [
                'execution' => $this->scriptExecution,
            ])
        );
    }

    public function failed(): void
    {
        $this->scriptExecution->finished_at = now();
        $this->scriptExecution->save();
        event(
            new Broadcast('execute-script-failed', [
                'execution' => $this->scriptExecution,
            ])
        );
    }
}
