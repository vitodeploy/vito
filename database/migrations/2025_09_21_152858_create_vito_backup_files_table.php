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
        Schema::create('vito_backup_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vito_backup_id');
            $table->string('name');
            $table->bigInteger('size');
            $table->string('status')->default('created');
            $table->string('path')->nullable();
            $table->timestamps();

            $table->foreign('vito_backup_id')->references('id')->on('vito_backups')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vito_backup_files');
    }
};
