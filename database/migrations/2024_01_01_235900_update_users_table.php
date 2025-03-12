<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedBigInteger('current_project_id')->nullable()->after('timezone');
        });
        User::query()->each(function (User $user): void {
            $project = $user->createDefaultProject();
            $user->servers()->update(['project_id' => $project->id]);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('current_project_id');
        });
    }
};
