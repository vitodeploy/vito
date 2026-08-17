<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Orphaned rows are deleted before the foreign keys are added, because a foreign key
     * cannot be created on a table that already violates it.
     */
    public function up(): void
    {
        DB::table('backups')
            ->whereNotExists(fn (Builder $query) => $query
                ->select(DB::raw(1))
                ->from('servers')
                ->whereColumn('servers.id', 'backups.server_id'))
            ->delete();

        DB::table('backup_files')
            ->whereNotExists(fn (Builder $query) => $query
                ->select(DB::raw(1))
                ->from('backups')
                ->whereColumn('backups.id', 'backup_files.backup_id'))
            ->delete();

        Schema::table('backups', function (Blueprint $table): void {
            $table->foreign('server_id')->references('id')->on('servers')->cascadeOnDelete();
        });

        // Widened before the foreign key is added: MySQL cannot MODIFY a column that is
        // already a foreign-key child, and a 32-bit column cannot reference a bigint parent.
        Schema::table('backup_files', function (Blueprint $table): void {
            $table->unsignedBigInteger('backup_id')->change();
        });

        Schema::table('backup_files', function (Blueprint $table): void {
            $table->foreign('backup_id')->references('id')->on('backups')->cascadeOnDelete();
        });
    }

    /**
     * The schema is fully reverted, but rows deleted by up() cannot be restored.
     */
    public function down(): void
    {
        Schema::table('backup_files', function (Blueprint $table): void {
            $table->dropForeign(['backup_id']);
        });

        Schema::table('backups', function (Blueprint $table): void {
            $table->dropForeign(['server_id']);
        });

        Schema::table('backup_files', function (Blueprint $table): void {
            $table->unsignedInteger('backup_id')->change();
        });
    }
};
