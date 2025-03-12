<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scripts', function (Blueprint $table): void {
            $table->unsignedInteger('project_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('scripts', function (Blueprint $table): void {
            $table->dropColumn('project_id');
        });
    }
};
