<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('source_controls', function (Blueprint $table) {
            $table->string('url')->nullable()->after('provider');
        });
    }

    public function down(): void
    {
        Schema::table('source_controls', function (Blueprint $table) {
            $table->dropColumn('url');
        });
    }
};
