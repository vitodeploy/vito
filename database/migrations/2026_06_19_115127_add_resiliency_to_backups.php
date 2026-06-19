<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('backups', function (Blueprint $table): void {
            $table->boolean('enabled')->default(true)->after('status');
        });

        Schema::table('backup_files', function (Blueprint $table): void {
            $table->text('message')->nullable()->after('status');
        });

        DB::table('backups')
            ->whereIn('status', ['stopped', 'deleting'])
            ->update(['enabled' => false]);

        Schema::table('backups', function (Blueprint $table): void {
            $table->string('status')->nullable()->change();
        });

        DB::table('backups')
            ->where('status', '!=', 'deleting')
            ->update(['status' => null]);
    }

    public function down(): void
    {
        DB::table('backups')
            ->whereNull('status')
            ->where('enabled', true)
            ->update(['status' => 'running']);

        DB::table('backups')
            ->whereNull('status')
            ->where('enabled', false)
            ->update(['status' => 'stopped']);

        Schema::table('backups', function (Blueprint $table): void {
            $table->string('status')->nullable(false)->change();
            $table->dropColumn('enabled');
        });

        Schema::table('backup_files', function (Blueprint $table): void {
            $table->dropColumn('message');
        });
    }
};
