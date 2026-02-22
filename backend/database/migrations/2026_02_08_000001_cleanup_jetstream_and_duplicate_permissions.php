<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Cleanup Jetstream teams remnants and remove duplicate
     * hyphenated permissions (IDs 1-10) that are no longer
     * referenced anywhere in the codebase.
     */
    public function up(): void
    {
        // 1. Drop Jetstream teams tables
        Schema::dropIfExists('team_invitations');
        Schema::dropIfExists('team_user');
        Schema::dropIfExists('teams');

        // 2. Remove current_team_id column from users table
        if (Schema::hasColumn('users', 'current_team_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('current_team_id');
            });
        }

        // 3. Remove old hyphenated permissions that are orphaned in the DB.
        //    The current seeder uses spaced names (e.g. "manage users").
        //    These hyphenated ones (e.g. "manage-users") are from a previous
        //    version and are not referenced in any code.
        $oldPermissions = [
            'manage-system',
            'manage-users',
            'manage-content',
            'view-admin',
            'moderate-chat',
            'moderate-forum',
            'view-reports',
            'manage-tickets',
            'manage-bans',
            'manage-gangs',
        ];

        // Remove any role_has_permissions links for old permissions first
        $oldPermissionIds = DB::table('permissions')
            ->whereIn('name', $oldPermissions)
            ->pluck('id');

        if ($oldPermissionIds->isNotEmpty()) {
            DB::table('role_has_permissions')
                ->whereIn('permission_id', $oldPermissionIds)
                ->delete();

            DB::table('model_has_permissions')
                ->whereIn('permission_id', $oldPermissionIds)
                ->delete();

            DB::table('permissions')
                ->whereIn('id', $oldPermissionIds)
                ->delete();
        }

        // 4. Clear Spatie permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-add current_team_id to users if needed
        if (!Schema::hasColumn('users', 'current_team_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('current_team_id')->nullable()->after('remember_token');
            });
        }

        // Re-create teams tables
        if (!Schema::hasTable('teams')) {
            Schema::create('teams', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->index();
                $table->string('name');
                $table->boolean('personal_team');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('team_user')) {
            Schema::create('team_user', function (Blueprint $table) {
                $table->id();
                $table->foreignId('team_id');
                $table->foreignId('user_id');
                $table->string('role')->nullable();
                $table->timestamps();
                $table->unique(['team_id', 'user_id']);
            });
        }

        if (!Schema::hasTable('team_invitations')) {
            Schema::create('team_invitations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('team_id')->constrained()->cascadeOnDelete();
                $table->string('email');
                $table->string('role')->nullable();
                $table->timestamps();
                $table->unique(['team_id', 'email']);
            });
        }

        // Re-add old permissions (they won't have role assignments though)
        $oldPermissions = [
            'manage-system', 'manage-users', 'manage-content', 'view-admin',
            'moderate-chat', 'moderate-forum', 'view-reports', 'manage-tickets',
            'manage-bans', 'manage-gangs',
        ];

        foreach ($oldPermissions as $perm) {
            DB::table('permissions')->insertOrIgnore([
                'name' => $perm,
                'guard_name' => 'sanctum',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
