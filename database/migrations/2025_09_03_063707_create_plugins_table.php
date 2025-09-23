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
        Schema::create('plugins', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique()->nullable();
            $table->string('version')->nullable();
            $table->text('description')->nullable();
            $table->string('repo')->nullable();
            $table->string('username')->nullable();
            $table->string('namespace');
            $table->string('folder');
            $table->boolean('is_enabled')->default(false);
            $table->boolean('is_installed')->default(false);
            $table->boolean('updates_available')->default(false);
            $table->timestamps();

            $table->index(['is_enabled']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plugins');
    }
};
