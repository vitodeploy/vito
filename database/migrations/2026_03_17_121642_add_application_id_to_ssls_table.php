<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ssls', function (Blueprint $table) {
            $table->unsignedBigInteger('application_id')->nullable()->after('site_id');
            $table->foreign('application_id')->references('id')->on('applications')->cascadeOnDelete();
            $table->index('application_id');
        });

        Schema::table('ssls', function (Blueprint $table) {
            $table->unsignedBigInteger('site_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('ssls', function (Blueprint $table) {
            $table->dropForeign(['application_id']);
            $table->dropIndex(['application_id']);
            $table->dropColumn('application_id');
        });

        Schema::table('ssls', function (Blueprint $table) {
            $table->unsignedBigInteger('site_id')->nullable(false)->change();
        });
    }
};
