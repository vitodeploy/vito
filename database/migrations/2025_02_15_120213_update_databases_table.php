<?php

use App\Actions\Database\SyncDatabases;
use App\Enums\ServerStatus;
use App\Models\Server;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('databases', function (Blueprint $table): void {
            $table->string('collation')->nullable();
            $table->string('charset')->nullable();
        });

        $servers = Server::query()->where('status', ServerStatus::READY)->get();

        /** @var Server $server */
        foreach ($servers as $server) {
            try {
                app(SyncDatabases::class)->sync($server);
            } catch (Exception $e) {
                Log::error($e->getMessage());
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('databases', function (Blueprint $table): void {
            $table->dropColumn('collation');
            $table->dropColumn('charset');
        });
    }
};
