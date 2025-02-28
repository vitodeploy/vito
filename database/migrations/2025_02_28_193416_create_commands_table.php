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
        Schema::create('commands', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('site_id');
            $table->string('name');
            $table->text('command');
            $table->timestamps();
        });

        // Create commands for each site currently in Vito if any commands are defined for its type
        Site::query()->chunk(100, function ($sites) {
            foreach ($sites as $site) {
                if (count($site->type()->predefinedCommands())) {
                    foreach ($site->type()->predefinedCommands() as $command) {
                        $site->commands()->create([
                            'name' => $command['name'],
                            'command' => $command['command'],
                        ]);
                    }
                }
            }
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commands');
    }
};
