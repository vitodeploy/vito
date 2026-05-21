<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('github_app', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('app_id');
            $table->string('app_slug');
            $table->string('name');
            $table->string('client_id');
            $table->longText('client_secret');
            $table->longText('webhook_secret');
            $table->longText('private_key');
            $table->string('html_url', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('github_app');
    }
};
