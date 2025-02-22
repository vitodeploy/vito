<?php

use App\Enums\ServerStatus;
use App\Models\Server;
use App\SSH\Services\Database\Database;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('databases', function (Blueprint $table) {
            $table->string('collation')->nullable();
            $table->string('charset')->nullable();
        });

        $servers = Server::query()->where('status', ServerStatus::READY)->get();

        /** @var Server $server */
        foreach ($servers as $server) {
            $service = $server->database();

            if (! $service) {
                continue;
            }

            /** @var Database $db */
            $db = $service->handler();

            $db->syncDatabases(false);
            $db->updateCharsets();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('databases', function (Blueprint $table) {
            $table->dropColumn('collation');
            $table->dropColumn('charset');
        });
    }
};
