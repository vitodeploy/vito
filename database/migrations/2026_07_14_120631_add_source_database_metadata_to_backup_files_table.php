<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('backup_files', function (Blueprint $table): void {
            $table->string('database_engine')->nullable()->after('size');
            $table->string('database_version')->nullable()->after('database_engine');
        });
    }

    public function down(): void
    {
        Schema::table('backup_files', function (Blueprint $table): void {
            $table->dropColumn(['database_engine', 'database_version']);
        });
    }
};
