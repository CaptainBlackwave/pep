<?php

namespace Database\Seeders;

use App\Core\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * This creates a test user for development/testing purposes.
     *
     * ⚠️ SECURITY WARNING:
     * Only use this in development or testing environments.
     * Remove or change these credentials in production!
     *
     * Default Credentials:
     * Username: testuser
     * Password: testpass123
     */
    public function run(): void
    {
        // Check if test user already exists
        if (User::where('username', 'testuser')->exists()) {
            $this->command->warn('Test user already exists. Skipping...');
            return;
        }

        // Check if user role exists
        $userRole = \Spatie\Permission\Models\Role::where('name', 'user')
            ->where('guard_name', 'sanctum')
            ->first();
        if (!$userRole) {
            $this->command->error('User role not found. Run RolePermissionSeeder first.');
            return;
        }

        // Look up first rank and location dynamically — never hardcode IDs
        $firstRank    = DB::table('ranks')->orderBy('required_exp')->first();
        $firstLocation = DB::table('locations')->orderBy('id')->first();

        if (!$firstRank || !$firstLocation) {
            $this->command->error('Ranks or locations not seeded. Run RanksTableSeeder and LocationSeeder first.');
            return;
        }

        $this->command->info('Creating test user...');

        // Create user with identity fields only (game stats go to PlayerProfile)
        $user = User::create([
            'name'                  => 'Test User',
            'username'              => 'testuser',
            'email'                 => 'testuser@example.com',
            'password'              => Hash::make('testpass123'),
            'email_verified_at'     => now(),
            'force_password_change' => false,
        ]);

        // User::booted() auto-creates a profile with defaults.
        // Update the profile with proper game stats.
        $user->profile()->update([
            'rank_id'     => $firstRank->id,
            'rank'        => $firstRank->name,
            'location_id' => $firstLocation->id,
            'location'    => $firstLocation->name,
            'level'       => 1,
            'experience'  => 0,
            'energy'      => 100,
            'max_energy'  => 100,
            'health'      => $firstRank->max_health ?? 100,
            'max_health'  => $firstRank->max_health ?? 100,
            'cash'        => 1000,
            'bank'        => 0,
            'bullets'     => 50,
            'respect'     => 0,
        ]);

        // Assign default user role
        $user->assignRole('user');

        $this->command->info('✅ Test user created successfully!');
        $this->command->newLine();
        $this->command->warn('⚠️  TEST USER CREDENTIALS:');
        $this->command->line('   Username: testuser');
        $this->command->line('   Email: testuser@example.com');
        $this->command->line('   Password: testpass123');
    }
}
