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
            $table->string('name');
            $table->string('type');
            $table->string('status')->default(NetworkStatus::CREATING->value);
            $table->string('addressing_pool')->default(NetworkAddressingPool::CGNAT->value);
            $table->string('cidr')->nullable();
            $table->string('cidr_canonical')->nullable();
            $table->unsignedInteger('port')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'name']);
            $table->unique(['project_id', 'cidr_canonical'], 'networks_project_cidr_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('networks');
    }
};
