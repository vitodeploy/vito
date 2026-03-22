<?php

use App\Enums\SslType;
use App\Models\Ssl;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ssls', function (Blueprint $table): void {
            $table->dropColumn('is_active');
        });

        Ssl::query()
            ->whereNotNull('site_id')
            ->where('type', SslType::CUSTOM)
            ->each(function (Ssl $ssl): void {
                $ssl->server_id = $ssl->site->server_id;
                $ssl->site_id = null;
                $ssl->save();
            });
    }

    public function down(): void
    {
        Schema::table('ssls', function (Blueprint $table): void {
            $table->boolean('is_active')->default(false)->after('email');
        });
    }
};
