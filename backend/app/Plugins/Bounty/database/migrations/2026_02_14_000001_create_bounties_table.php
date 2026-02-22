<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('bounties')) {
            Schema::create('bounties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('target_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('placed_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('claimed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('status')->default('active'); // active, claimed, expired
            $table->text('reason')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bounties');
    }
};
