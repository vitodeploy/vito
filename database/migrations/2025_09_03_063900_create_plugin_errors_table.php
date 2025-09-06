<?php

use App\Models\Plugin;
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
        Schema::create('plugin_errors', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Plugin::class)->constrained()->onDelete('cascade');
            $table->string('error_type');
            $table->text('error_message');
            $table->text('stack_trace')->nullable();
            $table->string('file')->nullable();
            $table->integer('line')->nullable();
            $table->json('context')->nullable();
            $table->boolean('is_fatal')->default(false);
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['plugin_id', 'occurred_at']);
            $table->index(['error_type', 'is_fatal']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plugin_errors');
    }
};
