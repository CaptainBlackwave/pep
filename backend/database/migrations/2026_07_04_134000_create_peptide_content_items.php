<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_content_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 40);
            $table->string('title', 220);
            $table->string('slug', 240);
            $table->string('category', 100)->default('General');
            $table->string('category_slug', 120)->default('general');
            $table->string('tag', 80)->nullable();
            $table->string('excerpt', 500)->nullable();
            $table->longText('body')->nullable();
            $table->string('icon', 40)->nullable();
            $table->unsignedTinyInteger('image_index')->default(0);
            $table->unsignedSmallInteger('read_minutes')->default(5);
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('downloads_count')->default(0);
            $table->unsignedInteger('comments_count')->default(0);
            $table->string('author_name', 140)->nullable();
            $table->string('author_badge', 80)->nullable();
            $table->json('metadata')->nullable();
            $table->string('status', 40)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['type', 'slug']);
            $table->index(['type', 'status', 'published_at']);
            $table->index(['type', 'category_slug', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_content_items');
    }
};
