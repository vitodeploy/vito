<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ssls', function (Blueprint $table): void {
            $table->boolean('is_wildcard')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('ssls', function (Blueprint $table): void {
            $table->dropColumn('is_wildcard');
        });
    }
};
