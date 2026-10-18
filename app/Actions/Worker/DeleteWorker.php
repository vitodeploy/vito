<?php

namespace App\Actions\Worker;

use App\Actions\Component\ValidateComponentProcess;
use App\Models\Component;
use App\Models\Worker;
use Illuminate\Validation\ValidationException;

class DeleteWorker
{
    /**
     * @throws ValidationException
     */
    public function delete(Worker $worker, ?Component $component = null): void
    {
        $component ??= $worker->component;
        app(ValidateComponentProcess::class)->run($component, function () use ($worker, $component): void {
            $worker->refresh();
            app(ValidateComponentProcess::class)->validate($worker->server, [], $component, 'worker', $worker);
            if ($worker->isSiteBootstrap()) {
                throw ValidationException::withMessages([
                    'name' => 'This worker is managed by its site. Delete the site to remove it.',
                ]);
            }

            $this->remove($worker);
        });
    }

    public function deleteForComponent(Component $component): void
    {
        foreach ($component->workers()->whereNull('component_capability_id')->get() as $worker) {
            $this->remove($worker);
        }
    }

    public function remove(Worker $worker): void
    {
        if ($worker->component_id !== null) {
            /** @var \App\Services\ProcessManager\ProcessManager $handler */
            $handler = $worker->server->processManager()->handler();
            $handler->delete($worker->id);
        }
        $worker->delete();
    }
}
