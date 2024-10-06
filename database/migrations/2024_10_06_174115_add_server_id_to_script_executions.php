<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('script_executions', function (Blueprint $table) {
            $table->unsignedInteger('server_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('script_executions', function (Blueprint $table) {
            $table->dropColumn('server_id');
        });
    }
};
