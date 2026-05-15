<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('firewall_rules', function (Blueprint $table): void {
            $table->string('port', 11)->change();
        });
    }

    public function down(): void
    {
        DB::table('firewall_rules')->where('port', 'like', '%:%')->delete();

        Schema::table('firewall_rules', function (Blueprint $table): void {
            $table->integer('port')->change();
        });
    }
};
