<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('community_vendor_reviews') || Schema::hasColumn('community_vendor_reviews', 'photo_urls')) {
            return;
        }

        Schema::table('community_vendor_reviews', function (Blueprint $table) {
            $table->json('photo_urls')->nullable()->after('tags');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('community_vendor_reviews') || !Schema::hasColumn('community_vendor_reviews', 'photo_urls')) {
            return;
        }

        Schema::table('community_vendor_reviews', function (Blueprint $table) {
            $table->dropColumn('photo_urls');
        });
    }
};
