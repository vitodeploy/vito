<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('source_controls', function (Blueprint $table) {
            $table->id();
            $table->string('provider');
            $table->json('provider_data')->nullable();
            $table->longText('access_token')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('source_controls');
    }
};
