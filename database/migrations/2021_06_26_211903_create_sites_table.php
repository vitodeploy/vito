<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('server_id')->index();
            $table->string('type');
            $table->json('type_data')->nullable();
            $table->string('domain')->index();
            $table->json('aliases')->nullable();
            $table->string('web_directory')->nullable();
            $table->string('path');
            $table->string('php_version')->nullable();
            $table->string('source_control')->nullable();
            $table->string('repository')->nullable();
            $table->string('branch')->nullable();
            $table->integer('port')->nullable();
            $table->string('status')->default('installing');
            $table->integer('progress')->default(0)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sites');
    }
};
