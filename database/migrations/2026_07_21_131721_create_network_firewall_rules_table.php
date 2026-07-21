<?php

use App\Enums\FirewallRuleStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('network_firewall_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('network_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('protocol')->nullable();
            $table->string('port')->nullable();
            $table->string('status')->default(FirewallRuleStatus::CREATING->value);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('network_firewall_rules');
    }
};
