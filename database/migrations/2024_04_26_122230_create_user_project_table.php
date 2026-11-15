<?php

use App\Enums\UserRole;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_project', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('project_id');
            $table->timestamps();
        });
        Project::all()->each(function (Project $project): void {
            $project->users->each(function (User $user) use ($project): void {
                $project->users()->updateOrCreate([
                    'user_id' => $user->id,
                ], [
                    'role' => $user->is_admin ? UserRole::OWNER : UserRole::USER,
                ]);
            });
        });
        User::all()->each(function (User $user): void {
            $user->ensureHasDefaultProject();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_project');
    }
};
