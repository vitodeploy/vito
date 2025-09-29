<?php

use App\Enums\UserRole;
use App\Models\Project;
use App\Models\User;
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
        Schema::table('user_project', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->string('email')->after('id')->nullable();
            $table->string('role')->after('project_id')->default(UserRole::USER);
        });
        Project::query()->chunk(100, function ($projects) {
            foreach ($projects as $project) {
                $project->users->each(function (User $user) use ($project): void {
                    $project->users()->updateOrCreate([
                        'user_id' => $user->id,
                    ], [
                        'role' => $user->is_admin ? UserRole::OWNER : UserRole::USER,
                    ]);
                });
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
