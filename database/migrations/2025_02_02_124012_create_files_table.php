<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('files', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('server_id');
            $table->string('server_user');
            $table->string('path');
            $table->string('type');
            $table->string('name');
            $table->unsignedBigInteger('size');
            $table->unsignedBigInteger('links');
            $table->string('owner');
            $table->string('group');
            $table->string('date');
            $table->string('permissions');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
