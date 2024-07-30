<?php

use App\Enums\DatabaseUserStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('database_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('server_id');
            $table->string('username');
            $table->longText('password')->nullable();
            $table->json('databases')->nullable();
            $table->string('host')->default('localhost');
            $table->string('status')->default(DatabaseUserStatus::CREATING);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('database_users');
    }
};
