<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('load_balancer_servers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('load_balancer_id');
            $table->string('ip');
            $table->integer('port');
            $table->integer('weight')->nullable();
            $table->boolean('backup');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('load_balancer_servers');
    }
};
