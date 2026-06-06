<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('sites', 'worker_environment')) {
            Schema::table('sites', function (Blueprint $table) {
                $table->text('worker_environment')->nullable()->after('env_variables');
            });
        }

        Schema::table('workers', function (Blueprint $table) {
            $table->text('environment')->nullable()->change();
        });

        DB::table('workers')->whereNotNull('environment')->orderBy('id')->chunkById(100, function ($workers) {
            foreach ($workers as $worker) {
                $decoded = json_decode((string) $worker->environment, true);

                if (! is_array($decoded)) {
                    continue;
                }

                $variables = array_is_list($decoded)
                    ? $decoded
                    : $this->convertMapToVariables($decoded);

                DB::table('workers')->where('id', $worker->id)->update([
                    'environment' => Crypt::encryptString(json_encode($variables)),
                ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn('worker_environment');
        });

        DB::table('workers')->whereNotNull('environment')->orderBy('id')->chunkById(100, function ($workers) {
            foreach ($workers as $worker) {
                try {
                    $decoded = json_decode(Crypt::decryptString((string) $worker->environment), true);
                } catch (Throwable) {
                    $decoded = null;
                }

                $map = [];
                if (is_array($decoded)) {
                    foreach ($decoded as $variable) {
                        if (is_array($variable) && isset($variable['key'])) {
                            $map[(string) $variable['key']] = (string) ($variable['value'] ?? '');
                        }
                    }
                }

                DB::table('workers')->where('id', $worker->id)->update([
                    'environment' => $map === [] ? null : json_encode($map),
                ]);
            }
        });

        Schema::table('workers', function (Blueprint $table) {
            $table->json('environment')->nullable()->change();
        });
    }

    /**
     * @param  array<string, mixed>  $map
     * @return array<int, array{key: string, value: string, is_secret: bool}>
     */
    private function convertMapToVariables(array $map): array
    {
        $variables = [];

        foreach ($map as $key => $value) {
            $variables[] = [
                'key' => $key,
                'value' => is_scalar($value) ? (string) $value : '',
                'is_secret' => (bool) preg_match('/PASSWORD|SECRET|TOKEN|KEY|PRIVATE/i', (string) $key),
            ];
        }

        return $variables;
    }
};
