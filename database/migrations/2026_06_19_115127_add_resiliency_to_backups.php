<?php

use App\Models\Backup;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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

        Backup::query()
            ->whereIn('status', ['stopped', 'deleting'])
            ->update(['enabled' => false]);
    }

    public function down(): void
    {
        Schema::table('backups', function (Blueprint $table): void {
            $table->dropColumn('enabled');
        });

        Schema::table('backup_files', function (Blueprint $table): void {
            $table->dropColumn('message');
        });
    }
};
