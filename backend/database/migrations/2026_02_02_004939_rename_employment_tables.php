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
        // (Removed table creation; handled by original migration)

        // Rename user_employment to player_employment if it hasn't been renamed yet
        if (Schema::hasTable('user_employment') && !Schema::hasTable('player_employment')) {
            Schema::rename('user_employment', 'player_employment');
        }

        // Update foreign key reference if needed
        if (Schema::hasTable('player_employment') && Schema::hasColumn('player_employment', 'job_id')) {
            Schema::table('player_employment', function (Blueprint $table) {
                $table->dropForeign(['job_id']);
                $table->renameColumn('job_id', 'position_id');
            });

            Schema::table('player_employment', function (Blueprint $table) {
                $table->foreign('position_id')->references('id')->on('employment_positions')->cascadeOnDelete();
            });
        }

        // Update work_shifts if needed
        if (Schema::hasTable('work_shifts') && Schema::hasColumn('work_shifts', 'employment_id')) {
            Schema::table('work_shifts', function (Blueprint $table) {
                $table->dropForeign(['employment_id']);
                $table->renameColumn('employment_id', 'player_employment_id');
            });

            Schema::table('work_shifts', function (Blueprint $table) {
                $table->foreign('player_employment_id')->references('id')->on('player_employment')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        // Only reverse the renames/alterations performed in up()
        if (Schema::hasTable('player_employment') && !Schema::hasTable('user_employment')) {
            Schema::rename('player_employment', 'user_employment');
        }
    }
};
