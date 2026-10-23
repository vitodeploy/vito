<?php

use App\Models\Worker;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Worker::query()->toBase()->whereNull('stdout_logfile')->chunkById(100, function ($workers): void {
            foreach ($workers as $worker) {
                $directory = $worker->component_id !== null
                    ? '/var/log/vito/workers/'.$worker->user
                    : ($worker->user === 'root' ? '/root' : '/home/'.$worker->user).'/.logs/workers';

                Worker::query()->toBase()->where('id', $worker->id)->whereNull('stdout_logfile')->update([
                    'stdout_logfile' => $directory.'/'.$worker->id.'.log',
                ]);
            }
        });
    }

    /**
     * Recorded paths remain valid when rolling back; no schema change to undo.
     */
    public function down(): void {}
};
