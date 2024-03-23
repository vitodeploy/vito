<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name')->index();
            $table->string('ssh_user')->nullable();
            $table->ipAddress('ip')->index()->nullable();
            $table->ipAddress('local_ip')->nullable();
            $table->unsignedInteger('provider_id')->nullable();
            $table->integer('port')->default(22);
            $table->string('os');
            $table->string('type');
            $table->json('type_data')->nullable();
            $table->string('provider');
            $table->json('provider_data')->nullable();
            $table->longText('authentication')->nullable();
            $table->longText('public_key')->nullable();
            $table->string('status')->default('installing');
            $table->integer('progress')->default(0);
            $table->string('progress_step')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servers');
    }
};
