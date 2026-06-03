<?php

use App\Enums\IpAddressStatus;
use App\Enums\IpAddressType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('server_ip_addresses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->string('ip');
            $table->unsignedTinyInteger('prefix_length')->default(32);
            $table->string('family')->default('inet');
            $table->string('interface')->nullable();
            $table->string('type')->default(IpAddressType::UNKNOWN->value);
            $table->string('status')->default(IpAddressStatus::CONFIGURING->value);
            $table->boolean('is_managed')->default(false);
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_dynamic')->default(false);
            $table->timestamps();

            $table->unique(['server_id', 'ip']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('server_ip_addresses');
    }
};
