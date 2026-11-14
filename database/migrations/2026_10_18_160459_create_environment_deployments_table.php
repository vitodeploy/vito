<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('environment_deployments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('environment_id')->constrained()->cascadeOnDelete();
            $table->string('status')->index();
            $table->string('current_stage')->nullable();
            $table->string('commit_id')->nullable();
            $table->json('commit_data')->nullable();
            $table->string('release');
            $table->string('branch');
            $table->foreignId('primary_component_id')->nullable()->constrained('components')->nullOnDelete();
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamp('stage_deadline_at')->nullable();
            $table->boolean('is_join')->default(false);
            $table->boolean('is_recovering')->default(false);
            $table->text('variables')->nullable();
            $table->unsignedBigInteger('variables_version')->default(0);
            $table->timestamps();
        });
        Schema::table('deployments', function (Blueprint $table): void {
            $table->foreignId('environment_deployment_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('current_stage')->nullable();
            $table->foreignId('previous_deployment_id')->nullable()->constrained('deployments')->nullOnDelete();
            $table->boolean('release_started')->default(false);
            $table->string('component_name')->nullable();
            $table->string('server_name')->nullable();
            $table->unique(['environment_deployment_id', 'component_id']);
        });
        Schema::table('environments', function (Blueprint $table): void {
            $table->json('shared_paths')->nullable();
            $table->unsignedInteger('keep_releases')->default(10);
            $table->foreignId('live_run_id')->nullable()->constrained('environment_deployments')->nullOnDelete();
            $table->unsignedBigInteger('variables_version')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('environments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('live_run_id');
            $table->dropColumn(['shared_paths', 'keep_releases', 'variables_version']);
        });
        Schema::table('deployments', function (Blueprint $table): void {
            $table->dropUnique(['environment_deployment_id', 'component_id']);
            $table->dropConstrainedForeignId('environment_deployment_id');
            $table->dropConstrainedForeignId('previous_deployment_id');
            $table->dropColumn(['current_stage', 'release_started', 'component_name', 'server_name']);
        });
        Schema::dropIfExists('environment_deployments');
    }
};
