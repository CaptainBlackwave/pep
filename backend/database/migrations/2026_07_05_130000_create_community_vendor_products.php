<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_vendor_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('community_vendors')->cascadeOnDelete();
            $table->string('name', 160);
            $table->string('slug', 180);
            $table->string('category', 80)->nullable();
            $table->string('strength', 80)->nullable();
            $table->string('package_size', 80)->nullable();
            $table->string('purity_label', 80)->nullable();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->string('currency_code', 3)->default('USD');
            $table->string('availability', 40)->default('in_stock');
            $table->string('image_url')->nullable();
            $table->json('tags')->nullable();
            $table->unsignedInteger('review_count')->default(0);
            $table->decimal('average_rating', 3, 2)->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status', 40)->default('published');
            $table->timestamps();

            $table->unique(['vendor_id', 'slug']);
            $table->index(['vendor_id', 'status', 'sort_order']);
            $table->index(['status', 'availability']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_vendor_products');
    }
};
