<?php

use App\Enums\ApplicationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table): void {
            $table->string('status')->default(ApplicationStatus::READY->value);
        });

        Schema::table('environments', function (Blueprint $table): void {
            $table->boolean('primary_component_initialized')->default(false);
            $table->text('last_error')->nullable();
        });

        Schema::table('components', function (Blueprint $table): void {
            $table->string('deploy_key_id')->nullable();
            $table->text('ssh_key')->nullable();
            $table->foreignId('deploy_key_source_control_id')->nullable()->constrained('source_controls')->restrictOnDelete();
            $table->string('deploy_key_repository')->nullable();
        });

        DB::table('environments')->whereIn('id', DB::table('components')->where('type', 'app')->select('environment_id'))
            ->update(['primary_component_initialized' => true]);
    }

    public function down(): void
    {
        Schema::table('components', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('deploy_key_source_control_id');
            $table->dropColumn(['deploy_key_id', 'ssh_key', 'deploy_key_repository']);
        });

        Schema::table('environments', function (Blueprint $table): void {
            $table->dropColumn(['primary_component_initialized', 'last_error']);
        });

        Schema::table('applications', function (Blueprint $table): void {
            $table->dropColumn('status');
        });
    }
};
