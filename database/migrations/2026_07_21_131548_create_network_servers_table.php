<?php

use App\Enums\NetworkServerStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('network_servers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('network_id')->constrained()->cascadeOnDelete();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->foreignId('server_ip_address_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip')->nullable();
            $table->text('public_key')->nullable();
            $table->text('private_key')->nullable();
            $table->string('status')->default(NetworkServerStatus::PENDING->value);
            $table->unsignedInteger('sync_attempts')->default(0);
            $table->timestamps();

            $table->unique(['network_id', 'server_id']);
            $table->unique(['network_id', 'ip']);
            $table->unique('server_ip_address_id');
            $table->index(['status', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('network_servers');
    }
};
