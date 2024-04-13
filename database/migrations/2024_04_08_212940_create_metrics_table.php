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
        Schema::create('metrics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('server_id');
            $table->decimal('load', 5, 2);
            $table->decimal('memory_total', 15, 0);
            $table->decimal('memory_used', 15, 0);
            $table->decimal('memory_free', 15, 0);
            $table->decimal('disk_total', 15, 0);
            $table->decimal('disk_used', 15, 0);
            $table->decimal('disk_free', 15, 0);
            $table->timestamps();

            $table->index(['server_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('metrics');
    }
};
