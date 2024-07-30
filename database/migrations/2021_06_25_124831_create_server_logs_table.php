<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('server_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('server_id');
            $table->unsignedBigInteger('site_id')->nullable();
            $table->string('type');
            $table->string('name');
            $table->string('disk');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('server_logs');
    }
};
