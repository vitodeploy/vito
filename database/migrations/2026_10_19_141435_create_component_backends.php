<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('components', function (Blueprint $table): void {
            $table->string('load_balancer_method')->nullable();
            $table->dropUnique(['environment_id', 'server_id', 'user']);
            $table->unique(['environment_id', 'server_id', 'user', 'type'], 'components_placement_unique');
        });
        Schema::table('firewall_rules', function (Blueprint $table): void {
            $table->boolean('component_managed')->default(false);
        });
        Schema::create('component_backends', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('component_id')->constrained()->cascadeOnDelete();
            $table->foreignId('target_component_id')->nullable()->constrained('components')->cascadeOnDelete();
            $table->ipAddress('ip')->nullable();
            $table->unsignedSmallInteger('port');
            $table->unsignedInteger('weight')->nullable();
            $table->boolean('backup')->default(false);
            $table->foreignId('firewall_rule_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('component_backends');
        DB::table('firewall_rules')->where('component_managed', true)->delete();
        DB::table('components')->where('type', 'load_balancer')->delete();
        Schema::table('firewall_rules', function (Blueprint $table): void {
            $table->dropColumn('component_managed');
        });
        Schema::table('components', function (Blueprint $table): void {
            $table->dropColumn('load_balancer_method');
            $table->dropUnique('components_placement_unique');
            $table->unique(['environment_id', 'server_id', 'user']);
        });
    }
};
