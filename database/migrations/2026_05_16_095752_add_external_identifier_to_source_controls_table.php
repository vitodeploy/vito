<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('source_controls', function (Blueprint $table): void {
            $table->string('external_identifier')->nullable();

            $table->unique(
                ['provider', 'external_identifier'],
                'source_controls_provider_external_identifier_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('source_controls', function (Blueprint $table): void {
            $table->dropUnique('source_controls_provider_external_identifier_unique');
            $table->dropColumn('external_identifier');
        });
    }
};
