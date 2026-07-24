<?php

use App\Enums\NetworkAddressingPool;
use App\Enums\NetworkStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('networks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('server_provider_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('type');
            $table->string('status')->default(NetworkStatus::CREATING->value);
            $table->string('addressing_pool')->default(NetworkAddressingPool::CGNAT->value);
            $table->string('cidr')->nullable();
            $table->string('cidr_canonical')->nullable();
            $table->unsignedInteger('port')->nullable();
            $table->string('external_id')->nullable();
            $table->string('region')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'name']);
            $table->unique(['project_id', 'server_provider_id', 'external_id'], 'networks_project_external_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('networks');
    }
};
