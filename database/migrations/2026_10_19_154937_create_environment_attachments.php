<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('environment_attachments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('environment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('server_id')->constrained()->restrictOnDelete();
            $table->morphs('attachable');
            $table->string('alias', 64)->nullable();
            $table->text('credentials');
            $table->json('access')->nullable();
            $table->foreignId('firewall_rule_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('pending');
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->unique(['environment_id', 'attachable_type', 'attachable_id'], 'environment_attachment_unique');
            $table->unique(['environment_id', 'attachable_type', 'alias'], 'environment_attachment_alias_unique');
        });
        Schema::table('database_users', function (Blueprint $table): void {
            $table->foreignId('environment_attachment_id')->nullable()->constrained()->restrictOnDelete();
        });
        Schema::table('firewall_rules', function (Blueprint $table): void {
            $table->foreignId('environment_attachment_id')->nullable()->constrained()->restrictOnDelete();
        });
        Schema::table('environment_deployments', function (Blueprint $table): void {
            $table->json('attachment_ids')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('environment_deployments', function (Blueprint $table): void {
            $table->dropColumn('attachment_ids');
        });
        Schema::table('database_users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('environment_attachment_id');
        });
        Schema::table('firewall_rules', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('environment_attachment_id');
        });
        Schema::dropIfExists('environment_attachments');
    }
};
