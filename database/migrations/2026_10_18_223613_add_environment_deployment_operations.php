<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('environment_deployments', function (Blueprint $table): void {
            $table->foreignId('rollback_of_id')->nullable()->constrained('environment_deployments')->nullOnDelete();
            $table->text('last_error')->nullable();
        });
        Schema::table('environments', function (Blueprint $table): void {
            $table->boolean('auto_deploy')->default(false);
        });
        Schema::table('git_hooks', function (Blueprint $table): void {
            $table->unsignedBigInteger('site_id')->nullable()->change();
            $table->string('repository')->nullable();
            $table->unique(['source_control_id', 'repository']);
        });
    }

    public function down(): void
    {
        DB::table('git_hooks')->whereNull('site_id')->delete();
        Schema::table('git_hooks', function (Blueprint $table): void {
            $table->dropUnique(['source_control_id', 'repository']);
            $table->dropColumn('repository');
            $table->unsignedBigInteger('site_id')->nullable(false)->change();
        });
        Schema::table('environments', function (Blueprint $table): void {
            $table->dropColumn('auto_deploy');
        });
        Schema::table('environment_deployments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('rollback_of_id');
            $table->dropColumn('last_error');
        });
    }
};
