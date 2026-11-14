<?php

use App\Enums\ComponentStatus;
use App\Enums\EnvironmentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_control_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('repository');
            $table->string('runtime');
            $table->timestamps();

            $table->unique(['project_id', 'name']);
        });

        Schema::create('environments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('branch');
            $table->string('runtime_version')->nullable();
            $table->string('status')->default(EnvironmentStatus::READY->value);
            $table->timestamps();

            $table->unique(['application_id', 'name']);
            $table->unique(['application_id', 'slug']);
        });

        Schema::create('components', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('environment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('server_id')->constrained()->restrictOnDelete();
            $table->foreignId('isolated_user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user');
            $table->string('name');
            $table->string('type');
            $table->string('path');
            $table->string('status')->default(ComponentStatus::PENDING->value);
            $table->text('last_error')->nullable();
            $table->boolean('needs_redeploy')->default(false);
            $table->timestamps();

            $table->unique(['environment_id', 'name']);
            $table->unique(['environment_id', 'server_id', 'user']);
        });

        Schema::table('environments', function (Blueprint $table): void {
            $table->foreignId('primary_component_id')->nullable()->constrained('components')->nullOnDelete();
        });

        Schema::create('component_capabilities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('component_id')->constrained()->cascadeOnDelete();
            $table->string('capability');
            $table->json('config')->nullable();
            $table->string('status')->default(ComponentStatus::PENDING->value);
            $table->timestamps();

            $table->unique(['component_id', 'capability']);
        });

        Schema::table('deployments', function (Blueprint $table): void {
            $table->unsignedBigInteger('site_id')->nullable()->change();
            $table->unsignedBigInteger('deployment_script_id')->nullable()->change();
            $table->foreignId('component_id')->nullable()->constrained()->nullOnDelete();
        });

        Schema::table('deployment_scripts', function (Blueprint $table): void {
            $table->unsignedBigInteger('site_id')->nullable()->change();
            $table->foreignId('environment_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('component_id')->nullable()->constrained()->cascadeOnDelete();
        });

        Schema::table('workers', function (Blueprint $table): void {
            $table->foreignId('component_id')->nullable()->constrained()->cascadeOnDelete();
        });

        Schema::table('cron_jobs', function (Blueprint $table): void {
            $table->foreignId('component_id')->nullable()->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        DB::table('applications')->delete();
        DB::table('deployments')->whereNull('site_id')->delete();

        Schema::table('cron_jobs', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('component_id');
        });

        Schema::table('workers', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('component_id');
        });

        Schema::table('deployment_scripts', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('component_id');
            $table->dropConstrainedForeignId('environment_id');
            $table->unsignedBigInteger('site_id')->nullable(false)->change();
        });

        Schema::table('deployments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('component_id');
            $table->unsignedBigInteger('site_id')->nullable(false)->change();
            $table->unsignedBigInteger('deployment_script_id')->nullable(false)->change();
        });

        Schema::dropIfExists('component_capabilities');

        Schema::table('environments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('primary_component_id');
        });

        Schema::dropIfExists('components');
        Schema::dropIfExists('environments');
        Schema::dropIfExists('applications');
    }
};
