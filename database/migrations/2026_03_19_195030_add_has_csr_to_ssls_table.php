<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ssls', function (Blueprint $table): void {
            $table->boolean('has_csr')->default(false)->after('is_wildcard');
        });
    }

    public function down(): void
    {
        Schema::table('ssls', function (Blueprint $table): void {
            $table->dropColumn('has_csr');
        });
    }
};
