<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('workers')->whereNotNull('environment')->orderBy('id')->chunkById(100, function ($workers): void {
            foreach ($workers as $worker) {
                try {
                    $variables = json_decode(Crypt::decryptString((string) $worker->environment), true);
                } catch (Throwable) {
                    continue;
                }

                if (! is_array($variables)) {
                    continue;
                }

                $changed = false;
                foreach ($variables as $index => $variable) {
                    if (! is_array($variable) || ($variable['is_secret'] ?? false) === true) {
                        continue;
                    }

                    if (preg_match('/_UR[LI]$|PASS|PWD|DSN|AUTH|CREDENTIAL/i', (string) ($variable['key'] ?? '')) === 1
                        || preg_match('#://[^/\s@]+@#', (string) ($variable['value'] ?? '')) === 1) {
                        $variables[$index]['is_secret'] = true;
                        $changed = true;
                    }
                }

                if ($changed) {
                    DB::table('workers')->where('id', $worker->id)->update([
                        'environment' => Crypt::encryptString((string) json_encode($variables)),
                    ]);
                }
            }
        });
    }

    public function down(): void {}
};
