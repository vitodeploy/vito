<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('firewall_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('server_id');
            $table->string('type');
            $table->string('protocol');
            $table->integer('port');
            $table->ipAddress('source')->default('0.0.0.0');
            $table->string('mask')->nullable();
            $table->text('note')->nullable();
            $table->string('status')->default('creating');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('firewall_rules');
    }
};
