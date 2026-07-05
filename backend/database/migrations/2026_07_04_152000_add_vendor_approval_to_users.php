<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users') || Schema::hasColumn('users', 'is_approved_vendor')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_approved_vendor')->default(false)->after('website_url');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('users') || !Schema::hasColumn('users', 'is_approved_vendor')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_approved_vendor');
        });
    }
};
