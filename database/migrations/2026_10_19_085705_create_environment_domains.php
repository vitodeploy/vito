<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ssls', function (Blueprint $table): void {
            $table->foreignId('environment_id')->nullable()->constrained()->cascadeOnDelete();
        });

        Schema::create('environment_domains', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('environment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('component_id')->constrained()->cascadeOnDelete();
            $table->string('host');
            $table->string('type');
            $table->foreignId('redirect_to_domain_id')->nullable()->constrained('environment_domains')->nullOnDelete();
            $table->string('ssl_method');
            $table->foreignId('ssl_id')->nullable()->constrained('ssls')->nullOnDelete();
            $table->boolean('force_ssl')->default(false);
            $table->string('status');
            $table->text('error')->nullable();
            $table->timestamps();
            $table->unique(['environment_id', 'host']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('environment_domains');
        DB::table('ssls')->whereNotNull('environment_id')->delete();
        Schema::table('ssls', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('environment_id');
        });
    }
};
