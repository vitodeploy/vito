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
        Schema::create('vito_backups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('frequency');
            $table->integer('keep_backups')->default(5);
            $table->unsignedBigInteger('storage_id');
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->foreign('storage_id')->references('id')->on('storage_providers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vito_backups');
    }
};
