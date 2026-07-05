<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('community_discussion_votes')) {
            Schema::create('community_discussion_votes', function (Blueprint $table) {
                $table->id();
                $table->string('target_type', 24);
                $table->unsignedBigInteger('target_id');
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->tinyInteger('value');
                $table->timestamps();

                $table->unique(['target_type', 'target_id', 'user_id'], 'community_discussion_vote_unique');
                $table->index(['target_type', 'target_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('community_discussion_votes');
    }
};
