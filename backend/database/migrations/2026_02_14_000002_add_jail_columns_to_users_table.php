<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'jail_until')) {
                $table->timestamp('jail_until')->nullable()->after('last_login_at');
            }

            if (!Schema::hasColumn('users', 'super_max_until')) {
                $table->timestamp('super_max_until')->nullable()->after('jail_until');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'super_max_until')) {
                $table->dropColumn('super_max_until');
            }

            if (Schema::hasColumn('users', 'jail_until')) {
                $table->dropColumn('jail_until');
            }
        });
    }
};
