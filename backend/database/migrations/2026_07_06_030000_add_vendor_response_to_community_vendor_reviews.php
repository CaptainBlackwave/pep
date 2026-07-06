<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('community_vendor_reviews', function (Blueprint $table) {
            $table->text('vendor_response')->nullable()->after('helpful_count');
            $table->timestamp('responded_at')->nullable()->after('vendor_response');
        });
    }

    public function down(): void
    {
        Schema::table('community_vendor_reviews', function (Blueprint $table) {
            $table->dropColumn(['vendor_response', 'responded_at']);
        });
    }
};
