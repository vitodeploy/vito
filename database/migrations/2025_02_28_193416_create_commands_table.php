<?php

use App\Models\Site;
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
        Schema::create('commands', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('site_id');
            $table->string('name');
            $table->text('command');
            $table->timestamps();
        });

        foreach (Site::all() as $site) {
            $site->commands()->createMany($site->type()->baseCommands());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commands');
    }
};
