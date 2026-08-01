<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('database_users')->whereNotNull('databases')->orderBy('id')->chunkById(100, function ($databaseUsers) {
            foreach ($databaseUsers as $databaseUser) {
                $decoded = json_decode((string) $databaseUser->databases, true);

                if (! is_array($decoded) || array_is_list($decoded)) {
                    continue;
                }

                DB::table('database_users')->where('id', $databaseUser->id)->update([
                    'databases' => json_encode(array_values($decoded)),
                ]);
            }
        });
    }

    public function down(): void
    {
        //
    }
};
