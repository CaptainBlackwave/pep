<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('achievements')) {
            Schema::create('achievements', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type')->default('crime_count');
            $table->integer('requirement')->default(1);
            $table->integer('reward_cash')->default(0);
            $table->integer('reward_xp')->default(0);
            $table->string('icon')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            });
        }
        if (!Schema::hasTable('user_achievements')) {
            Schema::create('user_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('achievement_id')->constrained()->cascadeOnDelete();
            $table->integer('progress')->default(0);
            $table->timestamp('earned_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'achievement_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_achievements');
        Schema::dropIfExists('achievements');
    }
};
