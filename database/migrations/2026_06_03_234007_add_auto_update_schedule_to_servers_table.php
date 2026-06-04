<?php

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
        Schema::table('servers', function (Blueprint $table) {
            if (! Schema::hasColumn('servers', 'auto_update')) {
                $table->boolean('auto_update')->default(false);
            }
            $table->string('auto_update_schedule')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn('auto_update_schedule');
            if (Schema::hasColumn('servers', 'auto_update')) {
                $table->dropColumn('auto_update');
            }
        });
    }
};
