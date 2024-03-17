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
            $table->json('provider_data')->nullable()->after('provider');
            $table->longText('access_token')->nullable(); // @TODO: remove this column
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('source_controls');
    }
};
