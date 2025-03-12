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
        Schema::table('firewall_rules', function (Blueprint $table): void {
            $table->string('name')->default('Undefined')->after('id');
            $table->ipAddress('source')->default(null)->nullable()->change();
        });

        DB::statement("UPDATE firewall_rules SET name = UPPER(protocol) WHERE protocol IN ('ssh', 'http', 'https')");
        DB::statement("UPDATE firewall_rules SET protocol = 'tcp' WHERE protocol IN ('ssh', 'http', 'https')");
        DB::statement("UPDATE firewall_rules SET source = null WHERE source = '0.0.0.0'");
        DB::statement("UPDATE firewall_rules SET mask = null WHERE mask = '0'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("UPDATE firewall_rules SET protocol = LOWER(name) WHERE protocol = 'tcp' AND LOWER(name) IN ('ssh', 'http', 'https')");
        DB::statement("UPDATE firewall_rules SET source = '0.0.0.0' WHERE source is null");
        DB::statement("UPDATE firewall_rules SET mask = '0' WHERE mask is null");

        Schema::table('firewall_rules', function (Blueprint $table): void {
            $table->dropColumn('name');
            $table->ipAddress('source')->default('0.0.0.0')->change();
        });
    }
};
