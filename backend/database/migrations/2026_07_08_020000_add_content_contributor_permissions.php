<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<int, string> */
    private array $permissions = [
        'community-content.create',
        'community-content.update',
        'community-content.publish',
        'community-content.manage',
    ];

    public function up(): void
    {
        if (!Schema::hasTable('permissions') || !Schema::hasTable('roles') || !Schema::hasTable('role_has_permissions')) {
            return;
        }

        $now = now();

        foreach ($this->permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permission, 'guard_name' => 'sanctum'],
                ['updated_at' => $now, 'created_at' => $now]
            );
        }

        foreach (['staff', 'content-editor'] as $role) {
            DB::table('roles')->updateOrInsert(
                ['name' => $role, 'guard_name' => 'sanctum'],
                ['updated_at' => $now, 'created_at' => $now]
            );
        }

        $this->assignPermissions('admin', $this->permissions);
        $this->assignPermissions('moderator', [
            'community-content.create',
            'community-content.update',
            'community-content.publish',
        ]);
        $this->assignPermissions('staff', [
            'community-content.create',
            'community-content.update',
        ]);
        $this->assignPermissions('content-editor', $this->permissions);
    }

    public function down(): void
    {
        if (!Schema::hasTable('permissions') || !Schema::hasTable('role_has_permissions')) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('name', $this->permissions)
            ->where('guard_name', 'sanctum')
            ->pluck('id');

        DB::table('role_has_permissions')
            ->whereIn('permission_id', $permissionIds)
            ->delete();

        DB::table('permissions')
            ->whereIn('id', $permissionIds)
            ->delete();
    }

    /**
     * @param array<int, string> $permissions
     */
    private function assignPermissions(string $roleName, array $permissions): void
    {
        $roleId = DB::table('roles')
            ->where('name', $roleName)
            ->where('guard_name', 'sanctum')
            ->value('id');

        if (!$roleId) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('name', $permissions)
            ->where('guard_name', 'sanctum')
            ->pluck('id');

        foreach ($permissionIds as $permissionId) {
            DB::table('role_has_permissions')->updateOrInsert([
                'permission_id' => $permissionId,
                'role_id' => $roleId,
            ]);
        }
    }
};
