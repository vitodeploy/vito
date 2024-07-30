<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('backup_files', function (Blueprint $table) {
            $table->string('restored_to')->after('status')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('backup_files', function (Blueprint $table) {
            $table->dropColumn('restored_to');
        });
    }
};
