<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('community_user_actions')) {
            Schema::create('community_user_actions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('action', 40);
                $table->string('target_type', 40);
                $table->string('target_key', 240);
                $table->timestamps();

                $table->unique(['user_id', 'action', 'target_type', 'target_key'], 'community_user_actions_unique');
                $table->index(['target_type', 'target_key']);
            });
        }

        if (!Schema::hasTable('community_user_blocks')) {
            Schema::create('community_user_blocks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('blocked_user_id')->constrained('users')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['user_id', 'blocked_user_id'], 'community_user_blocks_unique');
            });
        }

        if (Schema::hasTable('community_content_items') && !Schema::hasColumn('community_content_items', 'image_url')) {
            Schema::table('community_content_items', function (Blueprint $table) {
                $table->string('image_url', 2048)->nullable()->after('image_index');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('community_content_items') && Schema::hasColumn('community_content_items', 'image_url')) {
            Schema::table('community_content_items', function (Blueprint $table) {
                $table->dropColumn('image_url');
            });
        }

        Schema::dropIfExists('community_user_blocks');
        Schema::dropIfExists('community_user_actions');
    }
};
