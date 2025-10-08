<?php

use App\Enums\UserRole;
use App\Models\Project;
use App\Models\User;
use App\Models\UserProject;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('DROP INDEX IF EXISTS plugins_is_enabled_priority_index');
        }

        Schema::table('user_project', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->string('email')->after('id')->nullable();
            $table->string('role')->after('project_id')->default(UserRole::USER);
        });
        $admins = User::query()->where('is_admin', true)->get();
        Project::query()->chunk(100, function ($projects) use ($admins) {
            foreach ($projects as $project) {
                foreach ($admins as $admin) {
                    $project->users()->updateOrCreate([
                        'user_id' => $admin->id,
                    ], [
                        'email' => $admin->email,
                        'role' => UserRole::OWNER,
                    ]);
                }
                $project->users->each(function (UserProject $userProject) use ($project): void {
                    $project->users()->updateOrCreate([
                        'user_id' => $userProject->user_id,
                    ], [
                        'role' => $userProject->user?->is_admin ? UserRole::OWNER : UserRole::USER,
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
