<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('isolated_users', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->string('username');
            $table->text('ssh_key')->nullable();
            $table->json('installed_tooling')->nullable();
            $table->timestamps();
            $table->unique(['server_id', 'username']);
        });

        Schema::table('sites', function (Blueprint $table): void {
            $table->foreignId('isolated_user_id')
                ->nullable()
                ->after('server_id')
                ->constrained('isolated_users')
                ->nullOnDelete();
            $table->index('isolated_user_id');

            $table->string('user')->nullable()->default(null)->change();
        });

        DB::transaction(function (): void {
            $this->backfillIsolatedUsers();
        });
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table): void {
            $table->dropForeign(['isolated_user_id']);
            $table->dropIndex(['isolated_user_id']);
            $table->dropColumn('isolated_user_id');
        });

        Schema::dropIfExists('isolated_users');
    }

    private function backfillIsolatedUsers(): void
    {
        $defaultSshUser = config('core.ssh_user');
        $serverSshUsers = DB::table('servers')->pluck('ssh_user', 'id');
        $now = Carbon::now();

        DB::table('sites')
            ->whereNotNull('user')
            ->where('user', '!=', '')
            ->orderBy('server_id')
            ->orderBy('id')
            ->get(['id', 'server_id', 'user'])
            ->groupBy(fn ($site): string => $site->server_id.':'.$site->user)
            ->each(function ($sites) use ($serverSshUsers, $defaultSshUser, $now): void {
                $first = $sites->first();
                $rawSshUser = $serverSshUsers[$first->server_id] ?? null;
                $serverSshUser = $rawSshUser ?: $defaultSshUser;

                if ($first->user === $serverSshUser) {
                    return;
                }

                $iuserId = DB::table('isolated_users')->insertGetId([
                    'server_id' => $first->server_id,
                    'username' => $first->user,
                    'ssh_key' => null,
                    'installed_tooling' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('sites')
                    ->whereIn('id', $sites->pluck('id'))
                    ->update(['isolated_user_id' => $iuserId]);
            });
    }
};
