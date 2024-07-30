<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ssls', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('site_id');
            $table->string('type')->default('letsencrypt');
            $table->string('domains')->nullable();
            $table->longText('certificate')->nullable();
            $table->longText('pk')->nullable();
            $table->longText('ca')->nullable();
            $table->timestamp('expires_at');
            $table->string('status');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ssls');
    }
};
