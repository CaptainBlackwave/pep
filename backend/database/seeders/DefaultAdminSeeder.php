<?php

namespace Database\Seeders;

use App\Core\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DefaultAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * This creates a default admin user for quick deployment.
     *
     * ⚠️ SECURITY WARNING:
     * Only use this in development or immediately after deployment.
     * Change the password immediately after first login.
     *
     * Default Credentials:
     * Username: admin
     * Password: admin123
     */
    public function run(): void
    {
        // Check if admin user already exists
        if (User::where('username', 'admin')->exists()) {
            $this->command->warn('Admin user already exists. Skipping...');
            return;
        }

        // Check if admin role exists
        $adminRole = \Spatie\Permission\Models\Role::where('name', 'admin')
            ->where('guard_name', 'sanctum')
            ->first();
        if (!$adminRole) {
            $this->command->error('Admin role not found. Run RolePermissionSeeder first.');
            return;
        }

        // Look up first rank and location dynamically — never hardcode IDs
        $firstRank    = DB::table('ranks')->orderBy('required_exp')->first();
        $firstLocation = DB::table('locations')->orderBy('id')->first();

        if (!$firstRank || !$firstLocation) {
            $this->command->error('Ranks or locations not seeded. Run RanksTableSeeder and LocationSeeder first.');
            return;
        }

        $this->command->info('Creating default admin user...');

        $user = User::create([
            'name'                  => 'Administrator',
            'username'              => 'admin',
            'email'                 => 'admin@example.com',
            'password'              => Hash::make('admin123'),
            'email_verified_at'     => now(),
            'rank_id'               => $firstRank->id,
            'rank'                  => $firstRank->name,
            'location_id'           => $firstLocation->id,
            'location'              => $firstLocation->name,
            'level'                 => 1,
            'experience'            => 0,
            'energy'                => 100,
            'max_energy'            => 100,
            'health'                => $firstRank->max_health ?? 100,
            'max_health'            => $firstRank->max_health ?? 100,
            'cash'                  => 1000,
            'bank'                  => 0,
            'bullets'               => 50,
            'respect'               => 0,
            'force_password_change' => true,
        ]);

        // Assign admin role
        $user->assignRole('admin');

        $this->command->info('✅ Default admin user created successfully!');
        $this->command->newLine();
        $this->command->warn('⚠️  DEFAULT CREDENTIALS:');
        $this->command->line('   Username: admin');
        $this->command->line('   Password: admin123');
        $this->command->newLine();
        $this->command->error('🔒 CHANGE PASSWORD IMMEDIATELY AFTER FIRST LOGIN!');
    }
}
