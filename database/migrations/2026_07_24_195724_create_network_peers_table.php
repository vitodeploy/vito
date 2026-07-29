<?php

use App\Enums\NetworkPeerStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('network_peers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('network_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('ip');
            $table->string('public_key');
            $table->text('private_key')->nullable();
            $table->boolean('byo')->default(false);
            $table->string('status')->default(NetworkPeerStatus::PENDING->value);
            $table->timestamp('last_handshake_at')->nullable();
            $table->unsignedInteger('sync_attempts')->default(0);
            $table->timestamps();

            $table->unique(['network_id', 'name']);
            $table->unique(['network_id', 'ip']);
            $table->unique(['network_id', 'public_key']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('network_peers');
    }
};
