<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('queues', 'workers');
        Schema::table('workers', function (Blueprint $table): void {
            $table->unsignedInteger('site_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::rename('workers', 'queues');
    }
};
