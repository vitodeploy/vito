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
        Schema::table('dns_records', function (Blueprint $table) {
            $table->unsignedInteger('priority')->nullable()->after('ttl');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dns_records', function (Blueprint $table) {
            $table->dropColumn('priority');
        });
    }
};
