<?php

use App\Enums\FirewallRuleStatus;
use App\Enums\ServerNetworkRuleKind;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('server_network_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->foreignId('network_id')->constrained()->cascadeOnDelete();
            $table->foreignId('network_server_id')->constrained()->cascadeOnDelete();
            $table->foreignId('network_firewall_rule_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('kind')->default(ServerNetworkRuleKind::RULE->value);
            $table->string('name');
            $table->string('type')->default('allow');
            $table->string('protocol')->nullable();
            $table->string('port')->nullable();
            $table->string('source')->nullable();
            $table->unsignedTinyInteger('mask')->nullable();
            $table->string('status')->default(FirewallRuleStatus::CREATING->value);
            $table->timestamps();

            $table->index(['server_id', 'status']);
            $table->index('network_server_id');
            $table->index('network_firewall_rule_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('server_network_rules');
    }
};
