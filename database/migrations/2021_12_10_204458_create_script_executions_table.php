<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('script_executions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('script_id');
            $table->unsignedBigInteger('server_id');
            $table->string('user')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('script_executions');
    }
};
