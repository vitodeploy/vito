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
            $table->foreignId('domain_id')->nullable()->after('server_id')->constrained()->nullOnDelete();
            $table->json('csr_data')->nullable()->after('ca');
            $table->text('csr_passphrase')->nullable()->after('csr_data');
            $table->timestamp('expires_at')->nullable()->change();
            $table->boolean('is_wildcard')->default(false)->after('is_active');
            $table->boolean('has_csr')->default(false)->after('is_wildcard');

            $table->foreign('server_id')->references('id')->on('servers')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ssls', function (Blueprint $table): void {
            $table->dropForeign(['server_id']);
            $table->dropForeign(['domain_id']);
            $table->dropColumn(['server_id', 'domain_id', 'csr_data', 'csr_passphrase', 'is_wildcard', 'has_csr']);
            $table->unsignedBigInteger('site_id')->nullable(false)->change();
            $table->timestamp('expires_at')->nullable(false)->change();
        });
    }
};
