<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('command_executions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('command_id');
            $table->unsignedInteger('server_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('server_log_id')->nullable();
            $table->json('variables')->nullable();
            $table->string('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('command_executions');
    }
};
