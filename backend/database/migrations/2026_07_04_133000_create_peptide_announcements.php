<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('author_name', 120)->nullable();
            $table->string('title', 180);
            $table->string('slug', 200)->unique();
            $table->string('category', 80)->default('General');
            $table->string('category_slug', 100)->default('general');
            $table->string('icon', 40)->default('megaphone');
            $table->string('tone', 40)->default('purple');
            $table->string('excerpt', 320)->nullable();
            $table->text('body');
            $table->string('image_url')->nullable();
            $table->unsignedInteger('comments_count')->default(0);
            $table->unsignedInteger('views_count')->default(0);
            $table->boolean('is_pinned')->default(false);
            $table->string('status', 40)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'published_at']);
            $table->index(['category_slug', 'status']);
            $table->index(['is_pinned', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_announcements');
    }
};
