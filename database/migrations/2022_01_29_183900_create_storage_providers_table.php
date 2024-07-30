<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStorageProvidersTable extends Migration
{
    public function up(): void
    {
        Schema::create('storage_providers', function (Blueprint $table) {
            $table->id();
            $table->string('provider');
            $table->string('label')->nullable();
            $table->string('token', 1000)->nullable();
            $table->string('refresh_token', 1000)->nullable();
            $table->boolean('connected')->default(1);
            $table->timestamp('token_expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storage_providers');
    }
}
