<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->insert([
            'key' => 'membership_enabled',
            'value' => '0',
            'type' => 'boolean',
            'description' => 'Enable membership tier system',
            'group' => 'general',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'membership_enabled')->delete();
    }
};
