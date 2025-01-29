<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ssls', function (Blueprint $table) {
            $table->string('email')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('ssls', function (Blueprint $table) {
            $table->dropColumn('email');
        });
    }
};
