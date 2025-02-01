<?php

use App\Enums\SslType;
use App\Models\Site;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ssls', function (Blueprint $table) {
            $table->boolean('is_active')->default(false);
            $table->string('certificate_path')->nullable();
            $table->string('pk_path')->nullable();
            $table->string('ca_path')->nullable();
        });
        Site::query()->chunk(100, function ($sites) {
            foreach ($sites as $site) {
                foreach ($site->ssls as $ssl) {
                    if ($ssl->type === SslType::LETSENCRYPT) {
                        $ssl->certificate_path = $ssl->certificate;
                        $ssl->pk_path = $ssl->pk;
                        $ssl->ca_path = $ssl->ca;
                        $ssl->certificate = null;
                        $ssl->pk = null;
                        $ssl->ca = null;
                    }
                    if ($ssl->type === SslType::CUSTOM) {
                        $ssl->certificate_path = '/etc/ssl/'.$ssl->site->domain.'/cert.pem';
                        $ssl->pk_path = '/etc/ssl/'.$ssl->site->domain.'/privkey.pem';
                        $ssl->ca_path = '/etc/ssl/'.$ssl->site->domain.'/fullchain.pem';
                    }
                    $ssl->save();
                }
                $activeSSL = $site->ssls()->where('expires_at', '>=', now())->latest()->first();
                if ($activeSSL) {
                    $activeSSL->update(['is_active' => true]);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('ssls', function (Blueprint $table) {
            $table->dropColumn('is_active');
            $table->dropColumn('certificate_path');
            $table->dropColumn('pk_path');
            $table->dropColumn('ca_path');
        });
    }
};
