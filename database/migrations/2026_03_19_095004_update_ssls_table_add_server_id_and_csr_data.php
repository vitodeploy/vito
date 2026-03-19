<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ssls', function (Blueprint $table): void {
            $table->unsignedBigInteger('site_id')->nullable()->change();
            $table->unsignedBigInteger('server_id')->nullable()->after('site_id');
            $table->json('csr_data')->nullable()->after('ca');
            $table->text('csr_passphrase')->nullable()->after('csr_data');
            $table->timestamp('expires_at')->nullable()->change();

            $table->foreign('server_id')->references('id')->on('servers')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ssls', function (Blueprint $table): void {
            $table->dropForeign(['server_id']);
            $table->dropColumn(['server_id', 'csr_data', 'csr_passphrase']);
            $table->unsignedBigInteger('site_id')->nullable(false)->change();
            $table->timestamp('expires_at')->nullable(false)->change();
        });
    }
};
