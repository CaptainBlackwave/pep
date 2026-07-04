<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_vendors', function (Blueprint $table) {
            $table->id();
            $table->string('name', 160);
            $table->string('slug', 180)->unique();
            $table->string('logo_initials', 12)->nullable();
            $table->string('logo_text', 80)->nullable();
            $table->string('logo_class', 40)->nullable();
            $table->string('status_label', 40)->default('Trusted');
            $table->string('status_class', 40)->default('trusted');
            $table->text('description')->nullable();
            $table->string('website_url', 255)->nullable();
            $table->date('member_since')->nullable();
            $table->timestamp('last_active_at')->nullable();
            $table->unsignedInteger('review_count')->default(0);
            $table->decimal('average_rating', 3, 2)->default(0);
            $table->decimal('would_buy_again_percent', 5, 2)->default(0);
            $table->decimal('response_rate_percent', 5, 2)->default(0);
            $table->string('avg_response_time', 80)->nullable();
            $table->json('tags')->nullable();
            $table->json('top_products')->nullable();
            $table->string('status', 40)->default('published');
            $table->timestamps();

            $table->index(['status', 'average_rating']);
            $table->index(['status_class', 'status']);
        });

        Schema::create('community_vendor_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('community_vendors')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('author_name', 120)->nullable();
            $table->string('title', 160);
            $table->text('body');
            $table->unsignedTinyInteger('rating');
            $table->string('product_name', 120)->nullable();
            $table->unsignedInteger('helpful_count')->default(0);
            $table->boolean('would_buy_again')->nullable();
            $table->boolean('is_verified_buyer')->default(false);
            $table->json('tags')->nullable();
            $table->date('reviewed_at')->nullable();
            $table->string('status', 40)->default('pending');
            $table->timestamps();

            $table->index(['vendor_id', 'status', 'reviewed_at']);
            $table->index(['status', 'rating']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_vendor_reviews');
        Schema::dropIfExists('community_vendors');
    }
};
