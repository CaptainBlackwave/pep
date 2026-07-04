<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_lab_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submitted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('submitted_by_name')->nullable();
            $table->string('compound_name');
            $table->string('slug')->unique();
            $table->string('compound_type')->default('Peptide');
            $table->string('use_case')->nullable();
            $table->string('vendor_name');
            $table->string('batch_code');
            $table->string('lab_name');
            $table->date('tested_at')->nullable();
            $table->date('received_at')->nullable();
            $table->string('report_id')->nullable();
            $table->string('sample_type')->default('Injectable');
            $table->string('sample_condition')->default('Good');
            $table->decimal('purity_percent', 5, 2)->nullable();
            $table->decimal('water_content_percent', 5, 2)->nullable();
            $table->decimal('peptide_content_percent', 5, 2)->nullable();
            $table->string('identity_result')->default('Conforms');
            $table->string('overall_result')->default('Pass');
            $table->string('coa_filename')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('pending');
            $table->boolean('is_verified')->default(false);
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('comments_count')->default(0);
            $table->timestamps();

            $table->index(['status', 'tested_at']);
            $table->index(['compound_type', 'status']);
            $table->index(['vendor_name', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_lab_results');
    }
};
