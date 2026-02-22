<?php

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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'level')) {
                $table->integer('level')->default(1)->after('updated_at');
            }

            if (!Schema::hasColumn('users', 'experience')) {
                $table->bigInteger('experience')->default(0)->after('level');
            }

            if (!Schema::hasColumn('users', 'exp')) {
                // keep `exp` for compatibility with places that query `exp`
                $table->bigInteger('exp')->default(0)->after('experience');
            }

            if (!Schema::hasColumn('users', 'avatar')) {
                $table->string('avatar', 2048)->nullable()->after('exp');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['avatar', 'exp', 'experience', 'level'] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
