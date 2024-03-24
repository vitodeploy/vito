<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queues', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('server_id')->nullable();
            $table->unsignedBigInteger('site_id');
            $table->text('command');
            $table->string('user');
            $table->boolean('auto_start')->default(1);
            $table->boolean('auto_restart')->default(1);
            $table->integer('numprocs')->default(8);
            $table->boolean('redirect_stderr')->default(1);
            $table->string('stdout_logfile')->nullable();
            $table->string('status');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queues');
    }
};
