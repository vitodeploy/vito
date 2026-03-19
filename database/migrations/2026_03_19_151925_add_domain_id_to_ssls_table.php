<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ssls', function (Blueprint $table): void {
            $table->foreignId('domain_id')->nullable()->after('server_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ssls', function (Blueprint $table): void {
            $table->dropForeign(['domain_id']);
            $table->dropColumn('domain_id');
        });
    }
};
