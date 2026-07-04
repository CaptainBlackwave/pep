<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_discussion_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('description')->nullable();
            $table->string('icon')->default('discussions');
            $table->string('color')->default('purple');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('community_discussions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('community_discussion_categories')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('last_reply_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('author_name')->nullable();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('tag')->default('Discussion');
            $table->text('excerpt')->nullable();
            $table->longText('body');
            $table->string('status')->default('published');
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_locked')->default(false);
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('replies_count')->default(0);
            $table->timestamp('last_reply_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'is_pinned', 'last_reply_at']);
            $table->index(['category_id', 'status']);
        });

        Schema::create('community_discussion_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discussion_id')->constrained('community_discussions')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('author_name')->nullable();
            $table->longText('body');
            $table->string('attachment_name')->nullable();
            $table->unsignedInteger('votes_count')->default(0);
            $table->boolean('is_solution')->default(false);
            $table->timestamps();

            $table->index(['discussion_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_discussion_replies');
        Schema::dropIfExists('community_discussions');
        Schema::dropIfExists('community_discussion_categories');
    }
};
