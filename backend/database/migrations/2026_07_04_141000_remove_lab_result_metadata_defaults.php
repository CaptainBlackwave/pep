<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('community_lab_results')) {
            return;
        }

        DB::statement('ALTER TABLE community_lab_results MODIFY compound_type VARCHAR(255) NULL');
        DB::statement('ALTER TABLE community_lab_results MODIFY sample_type VARCHAR(255) NULL');
        DB::statement('ALTER TABLE community_lab_results MODIFY sample_condition VARCHAR(255) NULL');
        DB::statement('ALTER TABLE community_lab_results MODIFY identity_result VARCHAR(255) NULL');
        DB::statement('ALTER TABLE community_lab_results MODIFY overall_result VARCHAR(255) NULL');
    }

    public function down(): void
    {
        if (!Schema::hasTable('community_lab_results')) {
            return;
        }

        DB::statement("ALTER TABLE community_lab_results MODIFY compound_type VARCHAR(255) NOT NULL DEFAULT 'Peptide'");
        DB::statement("ALTER TABLE community_lab_results MODIFY sample_type VARCHAR(255) NOT NULL DEFAULT 'Injectable'");
        DB::statement("ALTER TABLE community_lab_results MODIFY sample_condition VARCHAR(255) NOT NULL DEFAULT 'Good'");
        DB::statement("ALTER TABLE community_lab_results MODIFY identity_result VARCHAR(255) NOT NULL DEFAULT 'Conforms'");
        DB::statement("ALTER TABLE community_lab_results MODIFY overall_result VARCHAR(255) NOT NULL DEFAULT 'Pass'");
    }
};
